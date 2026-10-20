<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Facture;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FactureController extends Controller
{
    private StockService $stock;

    public function __construct(StockService $stock)
    {
        $this->middleware('auth.check');
        $this->stock = $stock;
    }

    public function index(Request $request){
        $customers = Customer::all();
        $query = Facture::query();

        // Appliquer les filtres
        if ($request->filled('numero_facture')) {
            $query->where('numero_facture', 'like', '%' . $request->input('numero_facture') . '%');
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Filtrer sur la colonne `statut` renverrait des factures affichées
        // « Partiel » sous le libellé « Non payé » : le code n'y écrit jamais
        // « partiel », et une facture à moitié payée y reste « non payé ». Même
        // calcul que le badge et que la garde de suppression — montant_total moins
        // reste — pour que les trois disent la même chose.
        if ($request->filled('statut')) {
            $query->where(function ($q) use ($request) {
                match ($request->input('statut')) {
                    'payé'     => $q->where('montant_total', '>', 0)->where('reste', '<=', 0),
                    'partiel'  => $q->where('reste', '>', 0)->whereColumn('reste', '<', 'montant_total'),
                    'non payé' => $q->whereColumn('reste', '>=', 'montant_total'),
                    default    => $q,
                };
            });
        }

        if ($request->filled('livraison')) {
            $query->where('livraison', 'like', '%' . $request->input('livraison') . '%');
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->input('created_at')); // utiliser whereDate pour ignorer l'heure
        }

        // Ajout du tri : du plus récent au plus ancien
        $query->orderBy('created_at', 'desc');

        // Eager loading (client, boutique, paiements) + somme des intérêts en SQL
        $query->with(['customer', 'store', 'sales:id,numeroFacture,product_id,quantity,unite', 'paiements' => fn ($q) => $q->latest()])
            ->withSum('sales as interet_total', 'interet');

        // Périmètre magasin : un employé rattaché ne voit que les factures de
        // son magasin (et des emplacements qui lui sont rattachés).
        $user = auth()->user();
        if ($user->isStoreScoped()) {
            $query->whereIn('store_id', $user->accessibleStoreIds());
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        $dataTable = $query->paginate(50)->appends($request->query());

        return view('factures.index', compact('dataTable', 'customers'));
    }

    public function show($facture){
        $user = User::where('role_id', 2)->first();
        $invoice = Sale::where('numeroFacture', $facture)->get();
        $laFacture = Facture::where('numero_facture', $facture)->firstOrFail();
        $this->autoriserPerimetre($laFacture->store_id, 'Cette facture appartient à un autre magasin.');
        $customer = Customer::where('id', $laFacture->customer_id)->first();
        $paiements = Payment::where('facture_id', $laFacture->id)->get();
        return view('factures.show', compact('invoice', 'facture', 'laFacture', 'customer', 'user', 'paiements'));
    }



    /*public function printPdf($facture)
    {
        $laFacture   = Facture::where('numero_facture', $facture)->firstOrFail();
        $customer    = Customer::find($laFacture->customer_id);
        $invoice     = Sale::where('numeroFacture', $facture)->get();
        $company     = Company::current();
        $managerName = auth()->user()->name ?? 'Gérant';

        $pdf = Pdf::loadView('factures.pdf', compact(
                'facture', 'laFacture', 'customer', 'invoice', 'company', 'managerName'
            ))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'dpi'                  => 150,
            ]);

        return $pdf->stream("Facture_{$facture}.pdf");
        // Pour forcer le téléchargement à la place : $pdf->download("Facture_{$facture}.pdf")
    }*/


    public function printPdf($facture)
    {
        $laFacture = Facture::where('numero_facture', $facture)->firstOrFail();
        $customer  = Customer::find($laFacture->customer_id);
        $invoice   = Sale::where('numeroFacture', $facture)->get();
        $company   = Company::current();
        $managerName = auth()->user()->name ?? 'Gérant';

        $pdf = Pdf::loadView('factures.pdf', compact(
                'facture', 'laFacture', 'customer', 'invoice', 'company', 'managerName'
            ))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 150,
                'enable_javascript' => true,
            ]);

        return $pdf->stream("Facture_{$facture}.pdf");
    }


    public function bonDeCommande($facture)
    {
        $user = User::where('role_id', 2)->first();
        $invoice = Sale::where('numeroFacture', $facture)->get();
        $laFacture = Facture::where('numero_facture', $facture)->firstOrFail();
        $this->autoriserPerimetre($laFacture->store_id, 'Cette facture appartient à un autre magasin.');
        $customer = Customer::where('id', $laFacture->customer_id)->first();
        $paiements = Payment::where('facture_id', $laFacture->id)->get();

        return view('factures.bon_de_commande', compact('invoice', 'facture', 'laFacture', 'customer', 'user', 'paiements'));
    }

    // In app/Http/Controllers/FactureController.php

    public function bonDeSortie($facture)
    {
        $user = User::where('role_id', 2)->first();
        $invoice = Sale::where('numeroFacture', $facture)->get();
        $laFacture = Facture::where('numero_facture', $facture)->firstOrFail();
        $this->autoriserPerimetre($laFacture->store_id, 'Cette facture appartient à un autre magasin.');
        $customer = Customer::where('id', $laFacture->customer_id)->first();
        $paiements = Payment::where('facture_id', $laFacture->id)->get();

        return view('factures.bon_de_sortie', compact('invoice', 'facture', 'laFacture', 'customer', 'user', 'paiements'));
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'customerName' => 'required|string|max:255',
        ]);

        try {
            $customer = Customer::create([
                'customerName' => $request->customerName,
                'mark' => $request->mark ?? 'AMD',
                'tel' => $request->tel ?? '17288399',
                'address' => $request->address ?? 'kobaya',
                'fidelite' => 1
            ]);

            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function facturationForm(){
        $userStoreId = Auth::user()->forcedStoreId();
        $boutiques = Store::optionsFor(Auth::user());
        $customers = Customer::all();
        $countFactures = (Facture::max('id') ?? 0) + 1;
        $numeroFacture = date('Ym').''.sprintf("%04d", $countFactures);
        $funds = \App\Models\Fund::active()->orderBy('name')->get(['id', 'name']);
        return view('factures.create', compact('boutiques', 'customers', 'userStoreId', 'numeroFacture', 'funds'));
    }

    public function facturationStore(Request $request){
        // Un gérant de boutique ne peut facturer que pour sa propre boutique.
        if ($storeId = $this->ownStoreIdOverride()) {
            $request->merge(['store_id' => $storeId]);
        }

        $validatedData = $request->validate([
            'numeroFacture' => 'required',
            'avance' => 'required|numeric',
            'customer_id' => 'required',
            'store_id' => 'required',
            'products.*.productName' => 'required|string|max:255',
            // Quantité dans l'unité unique du produit (cartons, douzaines,
            // rouleaux…). qty_per_ctn n'est plus saisi : toujours 1.
            'products.*.cartons' => 'required|integer|min:1',
            'products.*.qty_per_ctn' => 'nullable|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.subtotal' => 'required|numeric|min:0',
            'products.*.image' => 'nullable|file|mimes:jpg,png,jpeg',
            'products.*.fund_id' => 'nullable|exists:funds,id',
        ]);

        $this->authorizeStore($request->store_id);

        try {
            DB::beginTransaction();
            $total_pcs = 0;
            $total_amount = 0;
            foreach ($validatedData['products'] as $product) {
                // 1) Reset per-iteration image state
                $uploadedImagePath = null;

                // 2) Upload if present with a UUID filename
                if (!empty($product['image'])) {
                    $ext = $product['image']->getClientOriginalExtension();
                    $filename = Str::uuid()->toString().'.'.$ext; // unique
                    $product['image']->move(public_path('products'), $filename);
                    $uploadedImagePath = $filename;
                }

                $productName = trim($product['productName']);
                $productName = str_replace('`', '', $productName); // Remove backticks that can cause SQL issues
                $produit = Product::firstOrNew(['libelle' => $productName]);
                $produit->sku         = $productName; // consider making this unique if needed
                // (La colonne products.qtityCtn n'existe pas : l'écrire faisait
                // échouer chaque facturation sur une erreur SQL.)
                $produit->description = $productName;

                if ($uploadedImagePath) {
                    // Only set/replace image if a new one was uploaded
                    $produit->image = $uploadedImagePath;
                } elseif (!$produit->exists && !$produit->image) {
                    // New product without an uploaded image: use default
                    $produit->image = 'default.png';
                }
                $produit->save();

                // Quantité et prix sont dans l'unité unique du produit, sans
                // conversion : 3 cartons à 85 000 = 255 000. Le sous-total est
                // recalculé ici plutôt que repris du navigateur.
                $product['quantity'] = (int) $product['cartons'];
                $product["prixTotal"] = round((float) $product['price'] * $product['quantity'], 2);
                $total_pcs += $product['quantity'];
                $total_amount += $product["prixTotal"];
                //dd($product['cartons'], $product['qty_per_ctn'], $product['subtotal']);
                // Atomic decrement to avoid overselling under concurrency
                // Propriétaire de la marchandise de la ligne : fonds propres
                // (vide) ou fonds d'un associé. Le stock est pris chez lui.
                $fundId = ! empty($product['fund_id']) ? (int) $product['fund_id'] : null;

                $this->stock->decrementOrFail((int) $request->store_id, (int) $produit->id, (int) $product['quantity'], [
                    'type'         => \App\Models\StockMovement::SALE,
                    'source_type'  => 'facture',
                    'document_ref' => $request->numeroFacture,
                    'customer_id'  => $request->customer_id,
                    'unit_price'   => $product['price'],
                    'fund_id'      => $fundId,
                ]);

                // Marge au coût du propriétaire, même règle que le point de vente.
                // (L'ancien calcul prenait le dernier « achat simple », ce qui
                // donnait des intérêts négatifs ou égaux au prix de vente.)
                $coutFonds = $fundId ? app(\App\Services\FundService::class)->unitCost($fundId, (int) $produit->id) : null;
                // Même règle qu'à la caisse : pas de vente sous le prix minimum du
                // produit ni sous son prix de revient (l'erreur annule toute la facture).
                $produit->verifierPrixDeVente((float) $product['price'], $coutFonds);
                $product["interet"] = $fundId
                    ? ($coutFonds > 0 ? ((float) $product['price'] - $coutFonds) * (int) $product['quantity'] : 0)
                    : $produit->interetPour((float) $product['price'], (int) $product['quantity']);
                Sale::create(
                    [
                        'numeroFacture' => $request->numeroFacture,
                        'product_id' => $produit->id,
                        'prix' => $product['price'],
                        'quantity' => $product['quantity'],
                        'prixTotal' => $product['prixTotal'],
                        'interet' => $product['interet'],
                        'store_id' => $request->store_id,
                        'user_id' => auth()->id(),
                        'fund_id' => $fundId,
                        'unit_cost' => $fundId ? $coutFonds : null,
                    ]
                );
            }

            if ($request->avance > $total_amount) {
                throw new \RuntimeException('Le montant de la commande est inférieur à l\'avance.');
            }
            $reste = $total_amount - $request->avance;

            $facture = Facture::create([
                'numero_facture' => $request->numeroFacture,
                'store_id' => $request->store_id,
                'user_id' => auth()->id(),
                'channel' => Facture::CHANNEL_FACTURATION,
                'customer_id' => $request->customer_id,
                'quantity' => $total_pcs,
                'montant_total' => $total_amount,
                'avance' => $request->avance,
                'reste' => $reste,
                'notes' => 'Facture '.$request->numero_facture.' enregistree',
                'statut' => 'non payé',
                'livraison' => 'non livré',
            ]);

            $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);
            Payment::create([
                'facture_id' => $facture->id,
                'versement' => $facture->avance,
                'total_paye' => $facture->avance,
                'paid_by' => 'cash',
                'reste' => $reste,
                'note' => "un premier versement de ".$facture->avance." effectué lors de l'emission de la facture comme avance.",
                'receipt_number' => $receiptNumber,
            ]);

            $sms = "Facture cree avec success";
            $customer = Customer::find($request->customer_id);
            $customer->recalculerCompteurs();
            $countCustomerInvoices = Facture::where('customer_id', $request->customer_id)->count();
            if ($countCustomerInvoices >= 5){
                $customer->fidelite = 1;
                $customer->save();
                $sms = "Facture cree avec success. Merci au client ".$customer->mark." pour sa fidelity";
            }
            DB::commit();
            // Redirect back with a success message
            if ($request->ajax()) {
                return response()->json(['message' => $sms]);
            } else {
                return redirect()->route('factures.index')->with('success', $sms);
            }
            //return redirect()->route('proformas.index')->with('success', 'Proforma créée avec succès.');
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['error' => 'Erreur : ' . $th->getMessage()], 500);
            } else {
                return redirect()->back()->with('error', 'Erreur lors de l\'enregistrement de ce proforma : ' . $th->getMessage());
            }
        }
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'numero_facture' => 'required|unique:factures',
            'customer_id' => 'required',
            'avance' => 'required|numeric',
            'notes' => 'nullable|string',
        ],[
            'numero_facture.required' => 'Le champ numéro de la facture est obligatoire',
            'numero_facture.unique' => 'Le champ numéro de la facture doit être unique',
            'customer_id.required' => 'Veuillez selectionner le client',
            'avance.required' => 'Veuillez entrer un montant, à la rigueure 0',
        ]);

        if (isset($request->customerName) && isset($request->mark) && isset($request->tel) && isset($request->email) && isset($request->address)) {
            $customer = Customer::create([
                'customerName' => $request->customerName,
                'mark' => $request->mark,
                'tel' => $request->tel,
                'email' => $request->email,
                'address' => $request->address,
            ]);

            $customer_id = $customer->id;
        }
        try {
            $facture = Facture::create([
                'numero_facture' => $request->numero_facture,
                'store_id' => $request->store_id,
                'customer_id' => $customer_id ?? $request->customer_id,
                'avance' => $request->avance,
                'notes' => $request->notes,
                'statut' => 'non payé',
                'livraison' => 'non livré',
            ]);

            $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);
            Payment::create([
                'facture_id' => $facture->id,
                'versement' => $facture->avance,
                'total_paye' => $facture->avance,
                'paid_by' => $request->paid_by,
                'reste' => $reste,
                'note' => "un premier versement de ".$facture->avance." effectué lors de l'emission de la facture comme avance.",
                'receipt_number' => $receiptNumber,
            ]);

            $sms = "Facture cree avec success";
            $countCustomerInvoices = Facture::where('customer_id', $request->customer_id)->count();
            if ($countCustomerInvoices >= 5){
                $customer = Customer::find($request->customer_id);
                $customer->fidelite = 1;
                $customer->save();
                $sms = "Facture cree avec success. Merci au client ".$customer->mark." pour sa fidelity";
            }
            // Redirect back with a success message
            $products = Product::all();
            return redirect()->route('sales.ajout',[$request->numero_facture, $request->avance, $request->store_id])->with([
                'success'=>$sms,
                'numeroFacture'=> $request->numero_facture,
                'products'=> $products,
                'avance' => $request->avance
            ]);
        } catch (\Throwable $th) {
            return back()->with('fall', 'une erreur lors de lajout, voici le message : '.$th->getMessage());
        }
    }

    public function edit($id){
        $facture = Facture::findOrFail($id);
        $this->autoriserPerimetre($facture->store_id, 'Cette facture appartient à un autre magasin.');
        return view('factures.edit', compact('facture'));
    }

    public function update(Facture $facture){
        $this->autoriserPerimetre($facture->store_id, 'Cette facture appartient à un autre magasin.');
        try {
            $facture->livraison = 'livré';
            $facture->save();
            return redirect()->back()->with('success', 'Votre facture a été mis à jour: livré');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'erreur lors de la modification'.$th->getMessage());
        }
    }

    public function destroy(Request $request, Facture $facture)
    {
        // Une vente encaissée ne se supprime pas : cette méthode efface
        // définitivement les ventes et les paiements (ni l'une ni l'autre de ces
        // tables n'a de `deleted_at`), l'encaissement disparaîtrait sans trace.
        // Contrôle placé avant la transaction : rien ne doit même être tenté.
        if ($facture->aRecuUnPaiement()) {
            $message = sprintf(
                'Facture n° %s : %s GNF ont déjà été encaissés. Une facture payée ou '
                .'partiellement payée ne peut pas être supprimée.',
                $facture->numero_facture,
                number_format($facture->montantEncaisse(), 0, ',', ' ')
            );

            if ($request->ajax()) {
                return response()->json(['error' => $message], 422);
            }

            return redirect()->route('factures.index')->with('error', $message);
        }

        $this->authorizeStore($facture->store_id);

        try {
            DB::beginTransaction();

            // Load related sales & payments once
            $facture->load('paiements');

            $sales = Sale::where('numeroFacture', $facture->numero_facture)->get();
            //dd($sales);

            // 1) Restore stock for each sale, then delete the sale
            foreach ($sales as $sale) {
                // Remise en stock à l'emplacement exact d'où la ligne était
                // sortie, tracée comme un retour. Une vente tierce n'avait
                // jamais puisé dans le stock : rien à y remettre.
                if (! $sale->is_third_party) {
                    $this->stock->increment((int) $sale->store_id, (int) $sale->product_id, (int) $sale->quantity, [
                        'type'         => \App\Models\StockMovement::SALE_RETURN,
                        'source_type'  => 'sale',
                        'source_id'    => $sale->id,
                        'document_ref' => $facture->numero_facture,
                        'customer_id'  => $facture->customer_id,
                        'unit_price'   => $sale->prix,
                        'fund_id'      => $sale->fund_id,
                        'reason'       => 'Suppression de la facture ' . $facture->numero_facture,
                    ]);
                }

                // delete sale row
                $sale->delete();
            }

            // 2) Delete related payments
            foreach ($facture->paiements as $payment) {
                $payment->delete();
            }

            // 3) Finally delete the facture
            $numero = $facture->numero_facture ?? $facture->numeroFacture ?? $facture->id;
            $clientId = $facture->customer_id;
            $facture->delete();

            // 4) Remettre les compteurs du client d'aplomb.
            //    Sans cela, total_taken gardait le montant d'une facture qui
            //    n'existe plus et le client restait débiteur d'une somme annulée.
            //    On repart des factures restantes plutôt que de soustraire, pour
            //    qu'une suppression ne puisse pas introduire un nouvel écart.
            $this->recalculerCompteursClient($clientId);

            DB::commit();

            $msg = "Facture #{$numero} deleted. Related sales/payments removed and stock restored.";

            if ($request->ajax()) {
                return response()->json(['message' => $msg]);
            }

            return redirect()->route('factures.index')->with('success', $msg);

        } catch (\Throwable $th) {
            DB::rollBack();
            $err = "Erreur lors de la suppression: ".$th->getMessage();

            if ($request->ajax()) {
                return response()->json(['error' => $err], 500);
            }

            return redirect()->back()->with('error', $err);
        }
    }

    /**
     * Recalcule total_taken, total_repaid et balance d'un client à partir de ses
     * factures. Même définition que la commande customers:recalculate-balances.
     */
    private function recalculerCompteursClient(?int $clientId): void
    {
        if (! $clientId) {
            return;
        }

        $factures = Facture::where('customer_id', $clientId)->get();
        $pris = round((float) $factures->sum('montant_total'), 2);
        $du = round((float) $factures->sum('reste'), 2);

        DB::table('customers')->where('id', $clientId)->update([
            'total_taken' => $pris,
            'total_repaid' => round($pris - $du, 2),
            'balance' => $du,
            'updated_at' => now(),
        ]);
    }
}
