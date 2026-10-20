<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Facture;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StoreProduct;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Purchase;
use App\Models\Achat;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Exports\SalesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Schema;

class SaleController extends Controller
{
    private StockService $stock;

    public function __construct(StockService $stock)
    {
        $this->middleware('auth.check');
        // Route::resource('sales', ...) n'appliquait aucune permission : un
        // comptable ou un responsable achats (sans aucun sales.* dans son preset)
        // pouvait créer/modifier des ventes, alors que seuls admin/manager/vendeur
        // le devraient (comptable n'a que sales.view en lecture).
        $this->middleware('permission:sales.view')->only(['index', 'show']);
        $this->middleware('permission:sales.create')->only(['create', 'store']);
        $this->middleware('permission:sales.edit')->only(['edit', 'update']);
        $this->middleware('permission:sales.delete')->only(['destroy']);
        $this->stock = $stock;
    }

    /**
     * Résout la période choisie (jour/semaine/mois/trimestre/semestre/année/
     * personnalisé) en une plage [début, fin] — même logique que
     * PaymentController::getFilteredPayments(), pour un comportement cohérent
     * entre les pages Ventes et Paiements. Retourne [null, null] si aucun
     * filtre de période n'est fourni (pas de plage imposée par défaut).
     */
    private function resolvePeriodRange(Request $request): array
    {
        if (! $request->filled('period')) {
            return [null, null];
        }

        $period = $request->get('period');
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'daily':
                $date = $request->get('date', now()->toDateString());
                $startDate = \Illuminate\Support\Carbon::parse($date)->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($date)->endOfDay();
                break;
            case 'weekly':
                $weekStart = $request->get('week_start', now()->startOfWeek()->toDateString());
                $startDate = \Illuminate\Support\Carbon::parse($weekStart)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();
                break;
            case 'monthly':
                $month = $request->get('month', now()->format('Y-m'));
                $startDate = \Illuminate\Support\Carbon::parse($month)->startOfMonth();
                $endDate = \Illuminate\Support\Carbon::parse($month)->endOfMonth();
                break;
            case 'quarterly':
                $quarter = (int) $request->get('quarter', ceil(now()->month / 3));
                $year = (int) $request->get('year', now()->year);
                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate = \Illuminate\Support\Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();
                break;
            case 'semestral':
                $semester = (int) $request->get('semester', now()->month <= 6 ? 1 : 2);
                $year = (int) $request->get('year', now()->year);
                $startMonth = $semester == 1 ? 1 : 7;
                $startDate = \Illuminate\Support\Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = $startMonth == 1
                    ? \Illuminate\Support\Carbon::create($year, 6, 30)->endOfDay()
                    : \Illuminate\Support\Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'yearly':
                $year = (int) $request->get('year', now()->year);
                $startDate = \Illuminate\Support\Carbon::create($year, 1, 1)->startOfDay();
                $endDate = \Illuminate\Support\Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'custom':
                $startDate = $request->filled('start_date') ? \Illuminate\Support\Carbon::parse($request->start_date)->startOfDay() : null;
                $endDate = $request->filled('end_date') ? \Illuminate\Support\Carbon::parse($request->end_date)->endOfDay() : null;
                break;
        }

        return [$startDate, $endDate];
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $produits = Product::all();
        $user = auth()->user();
        $boutiques = Store::visibleTo($user)->get();
        $employees = $user->isStoreScoped()
            ? User::whereIn('store_id', $user->accessibleStoreIds())->get()
            : User::whereNotNull('store_id')->get();

        // Un employé rattaché à un magasin ne voit que les ventes de son périmètre.
        $query = Sale::query()->visibleTo($user);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->filled('numeroFacture')) {
            $query->where('numeroFacture', 'like', '%' . $request->input('numeroFacture') . '%');
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', 'like', '%' . $request->input('product_id') . '%');
        }

        [$startDate, $endDate] = $this->resolvePeriodRange($request);
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Pagination serveur + eager loading : on ne charge plus (ni ne réécrit)
        // toute la table à chaque affichage — l'intérêt stocké fait foi.
        $dataTable = $query->with(['product.latestLigneCommande', 'user', 'facture'])
            ->latest()
            ->paginate(50)
            ->appends($request->query());

        // Pass the necessary data to the view, including options for filters
        $customers = Customer::all();
        return view('sales.index', compact('dataTable', 'produits','customers','boutiques','employees'));
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'customerName' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            $customer = Customer::create([
                'customerName' => $request->customerName,
                'mark' => $request->mark ?? 'AMD',
                'tel' => $request->tel ?? '17288399',
                'email' => $request->email ?? 'test@email.com',
                'address' => $request->address ?? 'kobaya',
                'fidelite' => 1
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Customer creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('sales.create');
    }

    public function ajout($numero_facture, $avance, $store_id)
    {
        $produits = Product::all();
        //dd($numero_facture);
        return view('sales.create', compact('numero_facture', 'produits', 'avance', 'store_id'));
    }

    public function voirSales($numero_facture){
        //dd('Here i am');
        $ligneVentes = Sale::where('numeroFacture', $numero_facture)->visibleTo(auth()->user())->with('store')->get();
        return view('sales.voirSales', compact('ligneVentes', 'numero_facture'));
    }

    /**
     * Export sales to Excel
     */
    public function exportExcel(Request $request)
    {
        $query = Sale::with('product');

        // Apply filters
        if ($request->filled('numeroFacture')) {
            $query->where('numeroFacture', 'like', '%' . $request->numeroFacture . '%');
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        [$startDate, $endDate] = $this->resolvePeriodRange($request);
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Périmètre magasin de l'utilisateur
        $query->visibleTo(auth()->user());

        $sales = $query->orderBy('created_at', 'desc')->get();

        $filename = 'ventes_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new SalesExport($request, $sales), $filename);
    }

    /**
     * Export sales to PDF
     */
    public function exportPDF(Request $request)
    {
        // Garde-fou mémoire : ce rapport peut couvrir des milliers de ventes
        // ("Toutes les ventes") et Dompdf est gourmand en RAM sur les gros tableaux.
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        $query = Sale::with('product');

        // Apply filters
        if ($request->filled('numeroFacture')) {
            $query->where('numeroFacture', 'like', '%' . $request->numeroFacture . '%');
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        [$startDate, $endDate] = $this->resolvePeriodRange($request);
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Sans aucun filtre, se limiter au mois en cours : Dompdf ne peut pas
        // rendre de façon fiable un tableau de plusieurs milliers de ventes
        // (testé : ~1.5 Go de RAM pour l'historique complet, quelle que soit
        // la limite mémoire). Pour l'historique complet, utiliser l'export Excel.
        // L'utilisateur peut choisir sa propre période via le filtre en haut de
        // l'index — ce repli ne s'applique que si aucun filtre n'est actif.
        if (!$request->anyFilled(['numeroFacture', 'product_id', 'period'])) {
            $query->whereDate('created_at', '>=', now()->startOfMonth())
                  ->whereDate('created_at', '<=', now()->endOfMonth());
        }

        // Périmètre magasin de l'utilisateur
        $query->visibleTo(auth()->user());

        // Garde-fou : au-delà de ce volume, Dompdf devient trop lent/gourmand en
        // RAM (testé : une année complète peut bloquer le serveur plusieurs
        // minutes). On préfère refuser proprement plutôt que de faire planter
        // la requête — l'Excel n'a pas cette limite.
        $count = (clone $query)->count();
        if ($count > 1500) {
            return back()->with(
                'error',
                "Cette période contient {$count} ventes — trop pour générer un PDF de façon fiable. "
                . 'Réduisez la période (ex : un mois au lieu d\'une année), ou utilisez l\'export Excel qui ne connaît pas cette limite.'
            );
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        // Get products for filter display
        $produits = Product::all();
        $company = \App\Models\Company::current();

        $pdf = Pdf::loadView('exports.sales-pdf', compact('sales', 'produits', 'company'));

        // Configure PDF
        $pdf->setPaper('A4', 'landscape'); // Use landscape for more columns
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            // Le logo est chargé depuis le disque (chemin local, pas asset()) donc pas besoin
            // de requêtes HTTP sortantes : ça évite un aller-retour qui, sur certains hébergements,
            // pouvait faire prendre plusieurs dizaines de secondes à cet export.
            'isRemoteEnabled' => false,
            'chroot' => public_path(),
        ]);

        $filename = 'ventes_' . now()->format('Y-m-d_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export filtered sales to Excel (from index page with current filters)
     */
    public function exportCurrentExcel(Request $request)
    {
        return $this->exportExcel($request);
    }

    /**
     * Export filtered sales to PDF (from index page with current filters)
     */
    public function exportCurrentPDF(Request $request)
    {
        return $this->exportPDF($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */



    /**
     * Prochain numéro de facture libre.
     *
     * `Facture::max('id')` ignore les factures en corbeille, alors que l'index
     * unique sur `factures.numero_facture`, lui, les compte toujours : après la
     * mise à la corbeille d'une facture, son numéro était réattribué et
     * l'insertion échouait sur « Duplicate entry ». On repart donc du maximum
     * corbeille comprise, puis on avance tant que le numéro est déjà pris.
     */
    private function prochainNumeroFacture(): string
    {
        return Facture::prochainNumero();
    }

    public function store(Request $request)
    {
        if ($storeId = $this->ownStoreIdOverride()) {
            $request->merge(['store_id' => $storeId]);
        }

        // Magasin de vente (caisse qui facture et encaisse) non précisé : il se
        // déduit de l'origine de la première ligne (l'entrepôt d'un magasin
        // facture pour ce magasin). L'origine de chaque ligne, elle, reste
        // l'emplacement réellement débité.
        if (! $request->filled('store_id')) {
            $premiere = collect($request->input('sales', []))->first(fn ($l) => ! empty($l['store_id']));
            $origine = $premiere ? Store::find($premiere['store_id']) : null;
            if ($origine) {
                $request->merge(['store_id' => $origine->pointDeVenteId()]);
            }
        }

        // Les lignes elles-mêmes n'étaient pas validées. Une quantité nulle créait
        // une facture à 0 GNF ; une quantité négative passait dès qu'une autre
        // ligne maintenait le total positif, et retranchait du montant sans
        // contrepartie — le stock, lui, restait protégé par StockService.
        // Seul le contrôle « avance > total » les rattrapait parfois, avec un
        // message sans rapport avec la cause.
        $request->validate([
            'numeroFacture' => 'required|string',
            'store_id'      => 'required|exists:stores,id',
            'customer_id'   => 'required|exists:customers,id',
            'avance'        => 'nullable|numeric|min:0',
            'paid_by'       => 'nullable|string',
            'notes'         => 'nullable|string',
            'sales'                              => 'required|array|min:1',
            // Produit du catalogue, ou — pour un produit pris chez un partenaire
            // et absent du catalogue — un nouveau produit (libellé + unité),
            // créé avec la vente.
            'sales.*.product_id'                 => 'nullable|required_without:sales.*.nouveau_libelle|exists:products,id',
            'sales.*.nouveau_libelle'            => 'nullable|string|max:225',
            'sales.*.nouvelle_unite'             => 'nullable|in:'.implode(',', array_keys(Product::UNITES)),
            // Emplacement source de la ligne (magasin, stock interne, entrepôt).
            // Absent : la ligne sort du magasin de la vente, comme avant.
            'sales.*.store_id'                   => 'nullable|exists:stores,id',
            // Propriétaire de la marchandise vendue : vide = fonds propres,
            // sinon le fonds d'un associé (choisi par le vendeur, ligne par ligne).
            'sales.*.fund_id'                    => 'nullable|exists:funds,id',
            'sales.*.quantity'                   => 'required|integer|min:1',
            'sales.*.prix'                       => 'required|numeric|min:0',
            'sales.*.partner_id'                 => 'nullable|exists:partners,id',
            'sales.*.purchase_price_from_partner'=> 'nullable|numeric|min:0',
        ], [
            'sales.required'            => 'Veuillez ajouter au moins un produit.',
            'sales.*.quantity.min'      => 'La quantité doit être d\'au moins 1.',
            'sales.*.quantity.integer'  => 'La quantité doit être un nombre entier.',
            'sales.*.prix.min'          => 'Le prix ne peut pas être négatif.',
        ]);

        // Le numéro affiché dans le POS est calculé à l'ouverture de la page : deux
        // caisses ouvertes en même temps reçoivent le même, et le premier à valider
        // le consomme. Plutôt que de renvoyer une erreur au vendeur, on reprend
        // simplement le prochain numéro libre au moment de l'enregistrement.
        // (La règle `unique:sales` ne suffisait pas : elle ne regardait pas la table
        // `factures`, où porte la contrainte qui faisait échouer l'insertion.)
        $numeroDemande = (string) $request->numeroFacture;
        $dejaPris = Facture::withTrashed()->where('numero_facture', $numeroDemande)->exists()
            || Sale::where('numeroFacture', $numeroDemande)->exists();

        if ($dejaPris) {
            $request->merge(['numeroFacture' => $this->prochainNumeroFacture()]);
        }

        $salesData = $request->input('sales') ?? [];
        $salesData = array_values(array_filter($salesData, fn($row) => !empty($row['product_id']) || !empty($row['nouveau_libelle'])));

        // Un produit hors catalogue n'est accepté que pris chez un partenaire :
        // il n'existe pas dans le stock.
        foreach ($salesData as $row) {
            if (empty($row['product_id']) && (empty($row['is_third_party']) || $row['is_third_party'] != '1')) {
                return redirect()->back()->with('error', "Un produit absent du catalogue ne peut être vendu que s'il est pris chez un partenaire.")->withInput();
            }
        }

        if (empty($salesData)) {
            return redirect()->back()->with('error', 'Veuillez ajouter au moins un produit.')->withInput();
        }

        // Emplacement de chaque ligne, contrôlé côté serveur : un employé
        // rattaché à un magasin ne peut vendre que depuis son périmètre, quel
        // que soit le store_id envoyé par le navigateur.
        $user = auth()->user();
        foreach ($salesData as $index => $data) {
            $lineStoreId = (int) ($data['store_id'] ?? 0) ?: (int) $request->store_id;
            if (! $user->canAccessStore($lineStoreId)) {
                return redirect()->back()
                    ->with('error', "Vous n'êtes pas autorisé à vendre depuis cet emplacement.")
                    ->withInput();
            }
            $salesData[$index]['store_id'] = $lineStoreId;
        }

        if (! $user->canAccessStore((int) $request->store_id)) {
            return redirect()->back()->with('error', "Vous n'êtes pas autorisé à vendre pour ce magasin.")->withInput();
        }

        DB::beginTransaction();
        try {
            $total_quantity = 0;
            $total_price = 0;

            // Toutes les lignes sont vérifiées avant la moindre écriture : si une
            // seule manque de stock, la vente entière est refusée avec le détail.
            $this->stock->assertAvailable(array_map(fn ($row) => [
                'store_id'   => $row['store_id'],
                'product_id' => $row['product_id'],
                'quantity'   => $row['quantity'],
                'fund_id'    => $row['fund_id'] ?? null,
            ], array_values(array_filter($salesData, fn ($row) => empty($row['is_third_party']) || $row['is_third_party'] != '1'))));
            $funds = app(\App\Services\FundService::class);

            $customerId = (int) $request->customer_id;

            foreach ($salesData as $data) {
                $isThirdParty = !empty($data['is_third_party']) && $data['is_third_party'] == "1";

                $data['prixTotal'] = $data['prix'] * $data['quantity'];

                if (empty($data['product_id'])) {
                    $data['product_id'] = $this->produitHorsCatalogue($data)->id;
                }
                $product = Product::find($data['product_id']);
                $libelle = $product?->libelle ?? 'Produit inconnu';

                if ($isThirdParty) {
                    // ====================== VENTE TIERCE ======================
                    if (empty($data['partner_id']) || empty($data['purchase_price_from_partner'])) {
                        throw new \Exception('Pour une vente tierce, le partenaire et le prix d\'achat sont obligatoires.');
                    }

                    $prixAmi = (float) $data['purchase_price_from_partner'];
                    $this->assertPriceCoversCost($libelle, (float) $data['prix'], $prixAmi);

                    $data['interet'] = $data['prixTotal'] - $prixAmi * $data['quantity'];
                    $data['is_third_party'] = true;

                } else {
                    // ====================== VENTE NORMALE ======================
                    // Le contrôle de marge passe avant le décrément de stock : inutile
                    // de consommer du stock pour une ligne qui va être refusée.
                    // Coût de revient du propriétaire de la marchandise : celui des
                    // fonds propres, ou celui des achats financés par l'associé.
                    $fundId = ! empty($data['fund_id']) ? (int) $data['fund_id'] : null;
                    $prix_achat = $fundId
                        ? $funds->unitCost($fundId, (int) $data['product_id'])
                        : ($product ? $this->resolveUnitCost($product) : 0.0);
                    // Plancher : le plus haut du prix de vente minimum (fiche produit)
                    // et du coût de revient du propriétaire de la marchandise.
                    if ($product) {
                        $product->verifierPrixDeVente((float) $data['prix'], $prix_achat);
                    } else {
                        $this->assertPriceCoversCost($libelle, (float) $data['prix'], $prix_achat);
                    }
                    $data['fund_id'] = $fundId;
                    $data['unit_cost'] = $prix_achat > 0 ? $prix_achat : null;

                    $this->stock->decrementOrFail((int) $data['store_id'], (int) $data['product_id'], (int) $data['quantity'], [
                        'type'         => \App\Models\StockMovement::SALE,
                        'source_type'  => 'facture',
                        'document_ref' => $request->numeroFacture,
                        'customer_id'  => $customerId,
                        'unit_price'   => $data['prix'],
                        'fund_id'      => $fundId,
                    ]);

                    // Coût inconnu : intérêt 0 plutôt qu'une marge fictive de 100 %.
                    $data['interet'] = $prix_achat > 0 ? $data['prixTotal'] - $prix_achat * $data['quantity'] : 0;
                    $data['is_third_party'] = false;
                }

                $total_quantity += $data['quantity'];
                $total_price += $data['prixTotal'];

                Sale::create([
                    'numeroFacture'               => $request->numeroFacture,
                    'product_id'                  => $data['product_id'],
                    'prix'                        => $data['prix'],
                    'quantity'                    => $data['quantity'],
                    'prixTotal'                   => $data['prixTotal'],
                    'interet'                     => $data['interet'],
                    'store_id'                    => $data['store_id'],
                    'user_id'                     => auth()->id(),
                    'is_third_party'              => $data['is_third_party'],
                    'partner_id'                  => $data['partner_id'] ?? null,
                    'purchase_price_from_partner' => $data['purchase_price_from_partner'] ?? null,
                    'fund_id'                     => $isThirdParty ? null : ($data['fund_id'] ?? null),
                    'unit_cost'                   => $isThirdParty ? null : ($data['unit_cost'] ?? null),
                ]);
            }

            // ====================== FACTURE & PAIEMENT ======================
            if ($request->avance > $total_price) {
                throw new \RuntimeException('Le montant de l\'avance ne peut pas être supérieur au total.');
            }

            $reste = $total_price - ($request->avance ?? 0);

            $facture = Facture::create([
                'numero_facture' => $request->numeroFacture,
                'store_id'       => $request->store_id,
                'user_id'        => auth()->id(),
                'channel'        => Facture::CHANNEL_POS,
                'customer_id'    => $request->customer_id,
                'montant_total'  => $total_price,
                'quantity'       => $total_quantity,
                'avance'         => $request->avance ?? 0,
                'notes'          => $request->notes,
                'reste'          => $reste,
                'statut'         => $reste == 0 ? 'payé' : 'non payé',
                'livraison'      => $reste == 0 ? 'livré' : 'non livré',
            ]);

            // Payment
            $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);
            Payment::create([
                'facture_id'   => $facture->id,
                'versement'    => $facture->avance,
                'total_paye'   => $facture->avance,
                'paid_by'      => $request->paid_by ?? 'cash',
                'reste'        => $reste,
                'note'         => "Premier versement effectué à l'émission de la facture.",
                'receipt_number' => $receiptNumber,
            ]);

            // Mise à jour Client
            // Convention du projet : balance = total_taken - total_repaid, donc
            // un solde POSITIF signifie que le client doit de l'argent. C'est ce
            // que lisent le tableau de bord (total des crédits), le contrôle qui
            // empêche d'archiver un client endetté, et le recalcul groupé de
            // PaymentController. Cette ligne appliquait le signe inverse : une
            // vente à crédit rendait le solde négatif, et le client disparaissait
            // des créances affichées.
            $customer = Customer::find($request->customer_id);
            $customer->recalculerCompteurs();

            DB::commit();

            return redirect()->route('factures.index')
                            ->with('success', 'Vente enregistrée avec succès !');

        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()
                            ->with('error', 'Erreur : ' . $th->getMessage())
                            ->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $sale)
    {
        $this->authorizeStore($sale->store_id);

        return view('sales.edit', compact('sale'));
    }

    public function pos(){
        $categories = Category::all();
        $produits = Product::with(['categories', 'latestLigneCommande', 'stores'])->get();
        $user = Auth::user();
        $userStoreId = $user->forcedStoreId();
        // Magasins de vente proposés (point de vente de la facture) et
        // emplacements sources possibles pour chaque ligne, tous deux limités
        // au périmètre de l'utilisateur. Listes issues de la base : aucun
        // entrepôt n'est codé en dur.
        $locations = Store::optionsFor($user);
        // Seuls les magasins encaissent : entrepôts et stocks internes ne sont
        // que des origines de marchandise, pas des caisses.
        $boutiques = $locations->where('type', Store::TYPE_MAGASIN)->values();
        if ($boutiques->isEmpty()) {
            $boutiques = $locations;
        }
        $locationIds = $locations->pluck('id')->all();
        // Origine → magasin qui facture, pour préremplir le magasin de vente.
        $caisseParOrigine = $locations->mapWithKeys(fn ($s) => [$s->id => $s->pointDeVenteId()]);
        // Stock par produit et par emplacement, pour afficher « Entrepôt 5 —
        // 1 500 disponibles » dans le choix d'origine de chaque ligne.
        $stockByProduct = $produits->mapWithKeys(fn ($p) => [
            $p->id => $p->stores
                ->filter(fn ($s) => in_array($s->id, $locationIds, true) && (int) $s->pivot->quantity > 0)
                // Une entrée par emplacement ET par propriétaire (fonds propres
                // ou fonds d'un associé) : le vendeur choisit le fonds à chaque ligne.
                ->map(fn ($s) => [
                    'id'    => $s->id,
                    'fund'  => $s->pivot->fund_id ? (int) $s->pivot->fund_id : null,
                    'owner' => \App\Models\Fund::labelFor($s->pivot->fund_id ? (int) $s->pivot->fund_id : null),
                    'qty'   => (int) $s->pivot->quantity,
                ])
                ->values(),
        ]);
        $locationLabels = $locations->mapWithKeys(fn ($s) => [$s->id => $s->full_name . ' (' . $s->type_label . ')']);
        // Unité de chaque produit (« carton », « cartons »…) pour les libellés
        // des lignes : la quantité saisie est directement dans cette unité.
        $unitesByProduct = $produits->mapWithKeys(fn ($p) => [$p->id => [
            'libelle'  => $p->unite_libelle,
            'singulier' => Product::motUnite($p->unite, 1),
            'pluriel'  => Product::motUnite($p->unite, 2),
        ]]);
        // Tout le catalogue, même en rupture : un produit que la boutique n'a
        // pas peut être pris chez un partenaire et vendu dans la même facture.
        $produitsTiers = $produits->sortBy('libelle')->map(fn ($p) => [
            'id'        => $p->id,
            'libelle'   => $p->libelle,
            'prix'      => (float) $p->price,
            'unite'     => $p->unite_libelle,
            'singulier' => Product::motUnite($p->unite),
        ])->values();
        $customers = Customer::all();
        $partners = Partner::all();
        $numeroFacture = $this->prochainNumeroFacture();
        return view('sales.pos', compact('produits', 'boutiques', 'customers', 'categories', 'userStoreId', 'numeroFacture', 'partners', 'stockByProduct', 'locationLabels', 'locationIds', 'caisseParOrigine', 'unitesByProduct', 'produitsTiers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $sale)
    {
        $sale = Sale::find($sale); // Use a meaningful variable name like $saleId instead of $sale
        if (!$sale) {
            return redirect()->route('sales.index')->with('error', 'Vente non trouvée.');
        }

        $this->authorizeStore($sale->store_id);

        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
                'prix' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            // Fetch the invoice associated with the sale
            $invoice = Facture::where('numero_facture', $sale->numeroFacture)->first();
            if (!$invoice) {
                DB::rollBack();
                return redirect()->route('sales.index')->with('error', 'Facture non trouvée.');
            }

            // Fetch the customer
            $customer = Customer::find($invoice->customer_id);
            if (!$customer) {
                DB::rollBack();
                return redirect()->route('sales.index')->with('error', 'Client non trouvé.');
            }

            // Fetch payments associated with the invoice
            $paiements = Payment::where('facture_id', $invoice->id)->get();

            // Calculate the remaining amount after the update
            $resteMontant = ($sale->quantity * $sale->prix) - ($request->quantity * $request->prix);
            $resteQuantity = ($sale->quantity) - ((int) $request->quantity);

            $totalPaid = $paiements->sum('versement');
            // Update the invoice
            $invoice->quantity -= $resteQuantity;
            $invoice->montant_total -= $resteMontant;
            $invoice->reste -= $resteMontant;
            $invoice->save();

            // Update payments
            foreach ($paiements as $pay) {
                $pay->reste -= $resteMontant;
                $pay->save();
            }

            // Les compteurs du client sont recalcules depuis les factures, une fois
            // la facture et les paiements a jour.
            $customer->recalculerCompteurs();

            // Update store product stock based on quantity delta
            $deltaQuantity = $request->quantity - $sale->quantity;
            // La vente d'origine reste dans le journal ; la modification y ajoute
            // un mouvement de correction de l'écart, tracé avec son auteur.
            $correction = [
                'type'         => \App\Models\StockMovement::CORRECTION,
                'source_type'  => 'sale',
                'source_id'    => $sale->id,
                'document_ref' => $sale->numeroFacture,
                'customer_id'  => $invoice->customer_id,
                'unit_price'   => $request->prix,
                'reason'       => 'Modification de la vente : quantité ' . $sale->quantity . ' → ' . $request->quantity,
                'fund_id'      => $sale->fund_id,
            ];
            if ($deltaQuantity > 0) {
                if (!$this->stock->decrementIfAvailable((int) $sale->store_id, (int) $sale->product_id, (int) $deltaQuantity, $correction)) {
                    DB::rollBack();
                    return redirect()->route('sales.index')->with('error', 'Stock insuffisant pour modifier cette vente.');
                }
            } elseif ($deltaQuantity < 0) {
                $this->stock->increment((int) $sale->store_id, (int) $sale->product_id, abs((int) $deltaQuantity), $correction);
            }

            // Update the sale record
            $sale->prix = $request->prix;
            $sale->quantity = $request->quantity;
            $sale->prixTotal = $request->quantity * $request->prix;

            $product = Product::find($sale->product_id);
            // Ligne d'un associé : son coût de revient, retenu à la vente.
            $prixAchat = $sale->fund_id
                ? (float) ($sale->unit_cost ?? app(\App\Services\FundService::class)->unitCost((int) $sale->fund_id, (int) $sale->product_id))
                : ($product ? $this->resolveUnitCost($product) : 0);
            // Même règle qu'à la création : modifier une vente ne doit pas permettre
            // de repasser sous le prix de revient.
            $this->assertPriceCoversCost($product?->libelle ?? 'Produit inconnu', (float) $request->prix, (float) $prixAchat);

            $sale->interet = $prixAchat > 0 ? ($request->prix - $prixAchat) * $request->quantity : 0;
            $sale->save();

            DB::commit();
            return redirect()->route('sales.index')->with('success', 'Vente modifiée avec succès.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('sales.index')->with('error', 'La vente n\'a pas été modifiée. Raison : ' . $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */


    public function exitSale($numero_facture)
    {
        // Ancien écran « quitter sans valider » : il supprimait n'importe quelle
        // facture d'après son numéro, sans contrôle. Seule une facture vide
        // (aucun article vendu) de son propre périmètre peut l'être ; une vraie
        // facture se supprime depuis la liste des factures (stock remis en place,
        // facture encaissée protégée).
        $facture = Facture::where('numero_facture', $numero_facture)->firstOrFail();
        $this->autoriserPerimetre($facture->store_id, 'Cette facture appartient à un autre magasin.');
        if (Sale::where('numeroFacture', $numero_facture)->exists()) {
            return redirect()->route('factures.index')->with('error', 'Cette facture contient déjà des articles : supprimez-la depuis la liste des factures.');
        }

        try {
            Payment::where('facture_id', $facture->id)->delete();
            $facture->delete();

            return redirect()->route('sales.index')->with('success', "Vous êtes parti sans valider, la vente a été annulé.");
        } catch (\Throwable $th) {
            // Log the error and return with an error message
            return redirect()->back()->with('error', 'Impossible de quitter cette page, une erreur est survenue: ' . $th->getMessage());
        }
    }

    /**
     * Coût d'achat unitaire : ligne de commande fournisseur la plus récente,
     * sinon dernier prix d'achat (table purchases), sinon price_sale.
     * Ne jamais retomber silencieusement sur 0 quand un achat existe —
     * c'est ce qui gonflait l'intérêt à 100 % du prix de vente.
     */
    /**
     * Interdit la vente à perte : le prix unitaire saisi doit couvrir le coût
     * d'acquisition — prix de revient du produit pour une vente normale, prix
     * payé au partenaire pour une vente tierce.
     *
     * Un coût nul signifie que le produit n'a jamais été rattaché à un achat :
     * il n'y a alors rien à comparer, la vente passe. Le contrôle ne peut pas
     * être plus strict que ce que l'historique d'achat permet de savoir.
     */
    private function assertPriceCoversCost(string $libelle, float $prixVente, float $cout): void
    {
        if ($cout > 0 && $prixVente < $cout) {
            throw new \RuntimeException(sprintf(
                'Vente à perte refusée pour « %s » : le prix de vente (%s GNF) est inférieur au prix de revient (%s GNF).',
                $libelle,
                number_format($prixVente, 0, ',', ' '),
                number_format($cout, 0, ',', ' ')
            ));
        }
    }

    private function resolveUnitCost(Product $product): float
    {
        return $product->coutUnitaire();
    }

    /**
     * Store a new partner via AJAX
     */
    public function storePartner(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $partner = Partner::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => true,
                'partner' => $partner,
                'message' => 'Partenaire créé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a quick third-party sale with a new product
     */
    /**
     * Vente tierce : des produits fournis par un partenaire, revendus au client.
     *
     * Accepte plusieurs lignes en une seule facture, et permet de créer le client
     * dans la foulée. Auparavant l'écran ne prenait qu'un produit à la fois, et un
     * client non renseigné faisait retomber la vente sur `Customer::first()` —
     * elle était alors imputée à quelqu'un au hasard, sans que personne le voie.
     */
    public function storeQuickThirdParty(Request $request)
    {
        $donnees = $request->validate([
            'store_id'   => 'nullable|exists:stores,id',
            'customer_id' => 'nullable|exists:customers,id',
            'nouveau_client.customerName' => 'nullable|string|max:255',
            'nouveau_client.tel'          => 'nullable|string|max:40',
            'nouveau_client.address'      => 'nullable|string|max:255',
            'avance'  => 'nullable|numeric|min:0',
            'paid_by' => 'nullable|string|max:40',
            'notes'   => 'nullable|string',

            'lignes'                                => 'required|array|min:1',
            'lignes.*.libelle'                      => 'required|string|max:255',
            'lignes.*.sku'                          => 'required|string|max:100',
            'lignes.*.prix'                         => 'required|numeric|min:0',
            'lignes.*.purchase_price_from_partner'  => 'required|numeric|min:0',
            'lignes.*.partner_id'                   => 'required|exists:partners,id',
            'lignes.*.quantity'                     => 'required|integer|min:1',
        ], [
            'lignes.required'              => 'Ajoutez au moins un produit.',
            'lignes.*.libelle.required'    => 'Le libellé du produit est obligatoire.',
            'lignes.*.sku.required'        => 'La référence du produit est obligatoire.',
            'lignes.*.quantity.min'        => 'La quantité doit être d\'au moins 1.',
            'lignes.*.partner_id.required' => 'Indiquez le partenaire qui fournit le produit.',
        ]);

        $lignes = array_values($donnees['lignes']);

        DB::beginTransaction();
        try {
            $storeId = $this->resoudreBoutique($request->input('store_id'));
            $customerId = $this->resoudreClient($request);

            $numeroFacture = $this->prochainNumeroFacture();

            $total = 0.0;
            $quantiteTotale = 0;

            foreach ($lignes as $ligne) {
                $prix = (float) $ligne['prix'];
                $prixAmi = (float) $ligne['purchase_price_from_partner'];
                $quantite = (int) $ligne['quantity'];

                // Le prix payé au partenaire est le prix de revient de la ligne :
                // vendre en dessous est refusé, comme pour une vente normale.
                $this->assertPriceCoversCost($ligne['libelle'], $prix, $prixAmi);

                $produit = $this->produitTierce($ligne);

                Sale::create([
                    'numeroFacture' => $numeroFacture,
                    'product_id'    => $produit->id,
                    'prix'          => $prix,
                    'quantity'      => $quantite,
                    'prixTotal'     => $prix * $quantite,
                    'interet'       => ($prix - $prixAmi) * $quantite,
                    'store_id'      => $storeId,
                    'user_id'       => auth()->id(),
                    'is_third_party' => true,
                    'partner_id'    => $ligne['partner_id'],
                    'purchase_price_from_partner' => $prixAmi,
                ]);

                $total += $prix * $quantite;
                $quantiteTotale += $quantite;
            }

            $avance = min((float) ($request->input('avance') ?? 0), $total);
            $reste = round($total - $avance, 2);

            $facture = Facture::create([
                'numero_facture' => $numeroFacture,
                'store_id'       => $storeId,
                'user_id'        => auth()->id(),
                'channel'        => Facture::CHANNEL_TIERS,
                'customer_id'    => $customerId,
                'montant_total'  => $total,
                'quantity'       => $quantiteTotale,
                'avance'         => $avance,
                'notes'          => $request->input('notes'),
                'reste'          => $reste,
                'statut'         => $reste <= 0 ? 'payé' : 'non payé',
                'livraison'      => $reste <= 0 ? 'livré' : 'non livré',
            ]);

            Payment::create([
                'facture_id'  => $facture->id,
                'versement'   => $avance,
                'total_paye'  => $avance,
                'paid_by'     => $request->input('paid_by') ?? 'cash',
                'reste'       => $reste,
                'note'        => 'Vente tierce — ' . ($request->input('notes') ?? ''),
                'receipt_number' => generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1),
            ]);

            // Les compteurs du client se déduisent de ses factures : sans cet appel,
            // la vente tierce n'apparaissait pas dans son solde.
            Customer::find($customerId)?->recalculerCompteurs();

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => sprintf(
                    'Vente tierce n° %s enregistrée : %d produit(s), %s GNF.',
                    $numeroFacture,
                    count($lignes),
                    number_format($total, 0, ',', ' ')
                ),
                'redirect' => route('factures.index'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Vente tierce : ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Boutique de la vente : celle choisie, sinon celle du vendeur.
     */
    private function resoudreBoutique($choisie): int
    {
        $user = auth()->user();
        $choisie = $user->forcedStoreId() ?? (! empty($choisie) ? (int) $choisie : $user->assignedStoreId());

        if (! $choisie) {
            throw new \RuntimeException('Aucune boutique sélectionnée pour cette vente.');
        }

        if (! $user->canAccessStore($choisie)) {
            throw new \RuntimeException("Vous n'êtes pas autorisé à vendre pour ce magasin.");
        }

        return (int) $choisie;
    }

    /**
     * Client de la vente : celui choisi, ou celui que le vendeur vient de saisir.
     *
     * Aucun repli sur un client existant : une facture attribuée au hasard fausse
     * le solde d'une personne qui n'a rien acheté.
     */
    private function resoudreClient(Request $request): int
    {
        if ($request->filled('customer_id')) {
            return (int) $request->input('customer_id');
        }

        $nom = trim((string) $request->input('nouveau_client.customerName'));

        if ($nom === '') {
            throw new \RuntimeException('Choisissez un client, ou renseignez le nom du nouveau client.');
        }

        return Customer::create([
            'customerName' => $nom,
            'mark'    => $this->marqueClientLibre($nom),
            'tel'     => trim((string) $request->input('nouveau_client.tel')) ?: 'N/A',
            'address' => trim((string) $request->input('nouveau_client.address')) ?: 'N/A',
            'fidelite' => 1,
        ])->id;
    }

    /**
     * Code client libre, dérivé du nom.
     *
     * `customers.mark` porte un index unique que MySQL applique sans regarder
     * `deleted_at` : on avance jusqu'à trouver un code qu'aucune fiche, même
     * archivée, ne détient déjà.
     */
    private function marqueClientLibre(string $nom): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $nom));
        $base = $base !== '' ? substr($base, 0, 3) : 'CLI';

        $i = 1;
        do {
            $marque = $base . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $i++;
        } while (Customer::withTrashed()->where('mark', $marque)->exists());

        return $marque;
    }

    /**
     * Produit correspondant à une ligne tierce, créé s'il n'existe pas encore.
     *
     * Le stock n'est pas touché : la marchandise vient du partenaire, elle n'a
     * jamais été en boutique.
     */
    /**
     * Produit pris chez un partenaire et absent du catalogue : retrouvé par
     * son libellé, sinon créé (sans stock) dans l'unité choisie, pour que la
     * vente, la facture et les rapports le désignent comme les autres.
     */
    private function produitHorsCatalogue(array $ligne): Product
    {
        $libelle = trim($ligne['nouveau_libelle']);
        $existant = Product::whereRaw('LOWER(libelle) = ?', [mb_strtolower($libelle)])->first();
        if ($existant) {
            return $existant;
        }

        return Product::create([
            'libelle'     => $libelle,
            'sku'         => 'TIERS-' . strtoupper(substr(md5($libelle . microtime()), 0, 8)),
            'description' => $libelle . ' (produit pris chez un partenaire)',
            'unite'       => $ligne['nouvelle_unite'] ?? 'pcs',
            'price'       => $ligne['prix'],
            'price_sale'  => $ligne['purchase_price_from_partner'] ?? 0,
            'image'       => '',
        ]);
    }

    private function produitTierce(array $ligne): Product
    {
        $produit = Product::where('sku', $ligne['sku'])->first();

        if ($produit) {
            return $produit;
        }

        return Product::create([
            'libelle'     => $ligne['libelle'],
            'sku'         => $ligne['sku'],
            'description' => $ligne['libelle'],
            'price'       => $ligne['prix'],
            'price_sale'  => $ligne['purchase_price_from_partner'],
        ]);
    }
}
