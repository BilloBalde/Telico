<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $period = $request->input('period', 'daily');
        $now = Carbon::now();

        $user = auth()->user();
        // Emplacements du périmètre de l'utilisateur ; un employé rattaché ne
        // peut pas demander le rapport d'un autre magasin en modifiant l'URL.
        $stores = Store::visibleTo($user)->orderBy('store_name')->get();
        $userStoreId = $user->forcedStoreId();
        $requested = $request->input('store_id');
        $storeId = $requested && $stores->contains('id', (int) $requested)
            ? (int) $requested
            : ($userStoreId ?: $stores->first()?->id);

        [$start, $end, $label] = $this->resolvePeriod($period, $request, $now);

        $salesQuery = Sale::with(['product', 'store'])
            ->whereBetween('created_at', [$start, $end]);

        if ($storeId) {
            $salesQuery->where('store_id', $storeId);
        }

        $sales = $salesQuery->orderBy('created_at')->get();

        $totalQuantity = $sales->sum('quantity');
        $totalAmount = $sales->sum('prixTotal');
        $totalProfit = $sales->sum('interet');

        return view('reports.sales', [
            'sales' => $sales,
            'stores' => $stores,
            'storeId' => $storeId,
            'period' => $period,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'totalQuantity' => $totalQuantity,
            'totalAmount' => $totalAmount,
            'totalProfit' => $totalProfit,
        ]);
    }

    /**
     * Ventes flash = ventes encaissées au point de vente (factures du canal
     * « pos »). Pour chacune : vendeur, date et heure, emplacement(s),
     * produits, quantités, montant, client. Synthèses par vendeur et par
     * emplacement, sur le jour, la semaine, le mois ou l'année.
     */
    public function flash(Request $request)
    {
        $period = $request->input('period', 'daily');
        [$start, $end, $label] = $this->resolvePeriod($period, $request, Carbon::now());
        $user = auth()->user();

        $lines = \App\Models\Sale::query()
            ->join('factures', 'factures.numero_facture', '=', 'sales.numeroFacture')
            ->whereNull('factures.deleted_at')
            // Ventes faites au comptoir : caisse ET ventes tierces (produits d'un
            // partenaire revendus depuis le point de vente, facturées à part).
            ->whereIn('factures.channel', [\App\Models\Facture::CHANNEL_POS, \App\Models\Facture::CHANNEL_TIERS])
            ->whereBetween('sales.created_at', [$start, $end])
            ->when($user->isStoreScoped(), fn ($q) => $q->whereIn('sales.store_id', $user->accessibleStoreIds()))
            ->when($request->filled('user_id'), fn ($q) => $q->where('sales.user_id', $request->input('user_id')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('sales.store_id', $request->input('store_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('sales.product_id', $request->input('product_id')))
            ->when($request->filled('type'), fn ($q) => $q->whereIn('sales.store_id', Store::where('type', $request->input('type'))->pluck('id')))
            ->select('sales.*', 'factures.customer_id as facture_customer_id', 'factures.id as facture_id');

        $byVendeur = (clone $lines)->reorder()
            ->select(DB::raw('sales.user_id, COUNT(DISTINCT sales.numeroFacture) as nb, SUM(sales.prixTotal) as ca, SUM(sales.quantity) as qty, SUM(sales.interet) as marge'))
            ->groupBy('sales.user_id')->get();
        $byStore = (clone $lines)->reorder()
            ->select(DB::raw('sales.store_id, COUNT(DISTINCT sales.numeroFacture) as nb, SUM(sales.prixTotal) as ca, SUM(sales.quantity) as qty, SUM(sales.interet) as marge'))
            ->groupBy('sales.store_id')->get();

        // Quantités par unité (cartons, pièces, rouleaux…) : on ne les
        // additionne jamais entre unités différentes.
        $qtyParUnite = (clone $lines)->reorder()
            ->select(DB::raw("sales.user_id, sales.store_id, COALESCE(sales.unite, 'pcs') as unite, SUM(sales.quantity) as qty"))
            ->groupBy('sales.user_id', 'sales.store_id', DB::raw("COALESCE(sales.unite, 'pcs')"))->get();
        $qtyVendeur = $qtyParUnite->groupBy('user_id')->map(fn ($g) => \App\Models\Product::resumeQuantites($g));
        $qtyStore = $qtyParUnite->groupBy('store_id')->map(fn ($g) => \App\Models\Product::resumeQuantites($g));

        $details = (clone $lines)
            ->with(['product:id,libelle', 'store:id,store_name,type', 'user:id,name'])
            ->orderByDesc('sales.created_at')
            ->paginate(100)->appends($request->query());

        $customers = \App\Models\Customer::withTrashed()->whereIn('id', $details->pluck('facture_customer_id')->filter()->unique())->pluck('customerName', 'id');
        $userNames = \App\Models\User::whereIn('id', $byVendeur->pluck('user_id')->filter())->pluck('name', 'id');
        $stores = Store::visibleTo($user)->orderBy('store_name')->get();
        $storeNames = Store::whereIn('id', $byStore->pluck('store_id'))->get()->keyBy('id');
        $vendeurs = \App\Models\User::whereIn('id', \App\Models\Sale::visibleTo($user)->distinct()->pluck('user_id')->filter())->orderBy('name')->get(['id', 'name']);
        $products = \App\Models\Product::orderBy('libelle')->get(['id', 'libelle']);

        $totals = [
            'nb'    => (int) $byVendeur->sum('nb'),
            'ca'    => (float) $byVendeur->sum('ca'),
            'qty'   => (int) $byVendeur->sum('qty'),
            'qty_unites' => \App\Models\Product::resumeQuantites($qtyParUnite),
            'marge' => (float) $byVendeur->sum('marge'),
        ];

        return view('reports.flash', compact(
            'period', 'label', 'start', 'end', 'byVendeur', 'byStore', 'details', 'customers',
            'userNames', 'stores', 'storeNames', 'vendeurs', 'products', 'totals', 'qtyVendeur', 'qtyStore'
        ));
    }

    /**
     * Ventes tierces : produits pris chez un partenaire et revendus (dans une
     * vente de caisse ou une vente tierce). Montre ce qui est dû à chaque
     * partenaire, ce qui lui a été réglé et le solde.
     */
    public function tiers(Request $request)
    {
        return view('reports.tiers', $this->donneesTiers($request));
    }

    /** Rapport des ventes tierces en PDF (mêmes filtres que la page). */
    public function tiersPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        $donnees = $this->donneesTiers($request, true);

        // Dompdf devient trop lent au-delà de quelques milliers de lignes :
        // l'Excel n'a pas cette limite.
        if ($donnees['details']->count() > 1500) {
            return back()->with('error', sprintf(
                'Cette période contient %d lignes de ventes tierces — trop pour un PDF. Réduisez la période ou utilisez l\'export Excel.',
                $donnees['details']->count()
            ));
        }

        $donnees['company'] = \App\Models\Company::current();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.ventes-tierces-pdf', $donnees)
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true]);

        return $pdf->download($this->nomFichierTiers($donnees) . '.pdf');
    }

    /** Rapport des ventes tierces en Excel : partenaires, détail, règlements. */
    public function tiersExcel(Request $request)
    {
        $donnees = $this->donneesTiers($request, true);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\VentesTiercesExport($donnees),
            $this->nomFichierTiers($donnees) . '.xlsx'
        );
    }

    private function nomFichierTiers(array $donnees): string
    {
        return 'ventes_tierces_' . $donnees['start']->format('Y-m-d') . '_au_' . $donnees['end']->format('Y-m-d');
    }

    /**
     * Données du rapport des ventes tierces, communes à la page et aux
     * exports. Pour un export, toutes les lignes (pas de pagination).
     */
    private function donneesTiers(Request $request, bool $export = false): array
    {
        $period = $request->input('period', 'monthly');
        [$start, $end, $label] = $this->resolvePeriod($period, $request, Carbon::now());
        $user = auth()->user();
        $peutRegler = $user->hasPermission('partners.settle');

        $lines = Sale::query()
            ->join('factures', 'factures.numero_facture', '=', 'sales.numeroFacture')
            ->whereNull('factures.deleted_at')
            ->where('sales.is_third_party', true)
            ->whereBetween('sales.created_at', [$start, $end])
            ->when($user->isStoreScoped(), fn ($q) => $q->whereIn('sales.store_id', $user->accessibleStoreIds()))
            ->when($request->filled('partner_id'), fn ($q) => $q->where('sales.partner_id', $request->input('partner_id')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('sales.store_id', $request->input('store_id')))
            ->select('sales.*', 'factures.customer_id as facture_customer_id');

        $details = (clone $lines)
            ->with(['product:id,libelle,unite', 'store:id,store_name,type', 'user:id,name', 'partner:id,name,phone'])
            ->orderByDesc('sales.created_at');
        $details = $export ? $details->get() : $details->paginate(100)->appends($request->query());

        // Par partenaire, sur la période : ventes, dû, marge, quantités par unité.
        $parPartenaire = (clone $lines)->reorder()
            ->select(DB::raw('sales.partner_id, COUNT(*) as nb, SUM(sales.prixTotal) as ca, SUM(sales.purchase_price_from_partner * sales.quantity) as du, SUM(sales.interet) as marge'))
            ->groupBy('sales.partner_id')->get()->keyBy('partner_id');
        $qtyParPartenaire = (clone $lines)->reorder()
            ->select(DB::raw("sales.partner_id, COALESCE(sales.unite, 'pcs') as unite, SUM(sales.quantity) as qty"))
            ->groupBy('sales.partner_id', DB::raw("COALESCE(sales.unite, 'pcs')"))->get()
            ->groupBy('partner_id')->map(fn ($g) => \App\Models\Product::resumeQuantites($g));

        // Soldes tous temps confondus (dû − réglé) : réservés à qui règle les partenaires.
        $dus = $peutRegler ? \App\Models\Partner::montantsDus() : collect();
        $regles = $peutRegler ? \App\Models\Partner::montantsRegles() : collect();
        $partenaireIds = $parPartenaire->keys()->merge($dus->keys())->unique()->filter();
        $partenaires = \App\Models\Partner::whereIn('id', $partenaireIds)->orderBy('name')->get()
            ->map(fn ($p) => (object) [
                'partner' => $p,
                'periode' => $parPartenaire[$p->id] ?? null,
                'qty'     => $qtyParPartenaire[$p->id] ?? '—',
                'du'      => (float) ($dus[$p->id] ?? 0),
                'regle'   => (float) ($regles[$p->id] ?? 0),
                'solde'   => (float) ($dus[$p->id] ?? 0) - (float) ($regles[$p->id] ?? 0),
            ])
            ->when($request->filled('partner_id'), fn ($c) => $c->filter(fn ($r) => $r->partner->id === (int) $request->input('partner_id')))
            ->values();

        $totals = [
            'ca'    => (float) $parPartenaire->sum('ca'),
            'du'    => (float) $parPartenaire->sum('du'),
            'marge' => (float) $parPartenaire->sum('marge'),
            'solde' => (float) $partenaires->sum('solde'),
        ];

        $reglements = $peutRegler
            ? \App\Models\PartnerSettlement::with(['partner:id,name', 'user:id,name'])
                ->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])
                ->when($request->filled('partner_id'), fn ($q) => $q->where('partner_id', $request->input('partner_id')))
                ->latest('paid_at')->latest('id')->get()
            : collect();

        $customers = \App\Models\Customer::withTrashed()->whereIn('id', $details->pluck('facture_customer_id')->filter()->unique())->pluck('customerName', 'id');
        $stores = Store::visibleTo($user)->orderBy('store_name')->get();
        $tousPartenaires = \App\Models\Partner::orderBy('name')->get(['id', 'name', 'phone']);

        return compact(
            'period', 'label', 'start', 'end', 'details', 'partenaires', 'totals', 'reglements',
            'customers', 'stores', 'tousPartenaires', 'peutRegler'
        );
    }

    /** Règlement fait à un partenaire (espèces, Orange Money…). */
    public function reglerPartenaire(Request $request)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'amount'     => 'required|numeric|min:1',
            'paid_at'    => 'required|date|before_or_equal:today',
            'mode'       => 'required|in:' . implode(',', array_keys(\App\Models\PartnerSettlement::MODES)),
            'note'       => 'nullable|string|max:500',
        ], [
            'amount.min'              => 'Le montant doit être positif.',
            'paid_at.before_or_equal' => 'La date du règlement ne peut pas être dans le futur.',
        ]);

        $partenaire = \App\Models\Partner::findOrFail($data['partner_id']);
        $solde = (float) (\App\Models\Partner::montantsDus()[$partenaire->id] ?? 0)
            - (float) (\App\Models\Partner::montantsRegles()[$partenaire->id] ?? 0);

        // On ne règle pas plus que ce qui est dû : une erreur de saisie
        // créerait une avance fictive chez le partenaire.
        if ($data['amount'] > $solde + 0.01) {
            return back()->withInput()->withErrors(['amount' => sprintf(
                'Le montant dépasse ce qui reste dû à %s (%s FG).',
                $partenaire->name,
                number_format(max(0, $solde), 0, ',', ' ')
            )]);
        }

        \App\Models\PartnerSettlement::create($data + ['user_id' => auth()->id()]);

        return back()->with('success', sprintf(
            'Règlement de %s FG à %s enregistré. Reste dû : %s FG.',
            number_format($data['amount'], 0, ',', ' '),
            $partenaire->name,
            number_format(max(0, $solde - $data['amount']), 0, ',', ' ')
        ));
    }

    private function resolvePeriod(string $period, Request $request, Carbon $now): array
    {
        switch ($period) {
            case 'weekly':
                $week = $request->input('week_start', $now->toDateString());
                $start = Carbon::parse($week)->startOfWeek();
                $end = $start->copy()->endOfWeek();
                $label = 'Semaine du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y');
                break;
            case 'monthly':
                $month = $request->input('month', $now->format('Y-m'));
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $label = $start->translatedFormat('F Y');
                break;
            case 'quarterly':
                $year = (int) $request->input('year', $now->year);
                $quarter = (int) $request->input('quarter', (int) ceil($now->month / 3));
                $startMonth = ($quarter - 1) * 3 + 1;
                $start = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $end = $start->copy()->addMonths(2)->endOfMonth();
                $label = "Trimestre {$quarter} - {$year}";
                break;
            case 'semestral':
                $year = (int) $request->input('year', $now->year);
                $semester = (int) $request->input('semester', $now->month <= 6 ? 1 : 2);
                $startMonth = $semester === 1 ? 1 : 7;
                $start = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $end = $start->copy()->addMonths(5)->endOfMonth();
                $label = "Semestre {$semester} - {$year}";
                break;
            case 'yearly':
                $year = (int) $request->input('year', $now->year);
                $start = Carbon::create($year, 1, 1)->startOfDay();
                $end = Carbon::create($year, 12, 31)->endOfDay();
                $label = "Année {$year}";
                break;
            case 'daily':
            default:
                $date = $request->input('date', $now->toDateString());
                $start = Carbon::parse($date)->startOfDay();
                $end = Carbon::parse($date)->endOfDay();
                $label = $start->toDateString();
                break;
        }

        return [$start, $end, $label];
    }
}
