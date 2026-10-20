<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Facture;
use App\Models\Logistic;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StoreProduct;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Sale;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class IndexController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    /**
     * Total des achats, toutes sources confondues : `achats` (module Proforma/
     * LigneCommande) est vide dans ce projet — les achats réels sont soit dans
     * `purchases` (ancien flux simple), soit dans `purchase_orders` (module Achat
     * actuel, avec coût de revient atterri). Se limiter à Achat::sum('grand_total')
     * donnait donc 0 partout sur le dashboard, quelle que soit l'activité réelle.
     */
    private function totalPurchases(?int $storeId = null): float
    {
        $fromPurchases = Purchase::when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('SUM(price * quantity) as total')
            ->value('total');

        $fromOrders = PurchaseOrder::where('status', '!=', 'cancelled')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->sum('total_landed_cost_gnf');

        return (float) $fromPurchases + (float) $fromOrders;
    }

    /**
     * Même total que totalPurchases(), réparti par mois (1-12) pour le graphique
     * "Ventes & Achats".
     */
    private function monthlyPurchases(?int $storeId = null): array
    {
        $fromPurchases = DB::table('purchases')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('SUM(price * quantity) as total, MONTH(created_at) as month')
            ->groupBy('month')
            ->pluck('total', 'month');

        $fromOrders = DB::table('purchase_orders')
            ->where('status', '!=', 'cancelled')
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('SUM(total_landed_cost_gnf) as total, MONTH(created_at) as month')
            ->groupBy('month')
            ->pluck('total', 'month');

        $combined = [];
        foreach (range(1, 12) as $month) {
            $combined[$month] = (float) ($fromPurchases[$month] ?? 0) + (float) ($fromOrders[$month] ?? 0);
        }

        return $combined;
    }

    /**
     * Chiffres multi-emplacements du tableau de bord. Requêtes agrégées : le
     * coût ne dépend pas du nombre de magasins ou d'entrepôts.
     */
    private function locationOverview($user): array
    {
        $scope = fn ($q, $column = 'store_id') => $user->isStoreScoped() ? $q->whereIn($column, $user->accessibleStoreIds()) : $q;
        $warehouseIds = Store::where('type', Store::TYPE_ENTREPOT)->pluck('id');

        $periods = [
            'jour'    => [Carbon::today(), Carbon::today()->endOfDay()],
            'semaine' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'mois'    => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'annee'   => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
        ];

        $sales = [];
        foreach ($periods as $key => $range) {
            $row = $scope(Sale::query())->whereBetween('created_at', $range)
                ->selectRaw('COALESCE(SUM(prixTotal),0) as ca, COALESCE(SUM(interet),0) as marge')->first();
            $direct = $scope(Sale::query())->whereBetween('created_at', $range)
                ->whereIn('store_id', $warehouseIds)->sum('prixTotal');
            // Part de ces ventes portant sur des marchandises financées par des
            // associés (facturée ; leur résultat à partager est dans leurs relevés).
            $associes = $scope(Sale::query())->whereBetween('created_at', $range)->whereNotNull('fund_id')
                ->selectRaw('COALESCE(SUM(prixTotal),0) as ca, COALESCE(SUM(interet),0) as marge')->first();
            $sales[$key] = [
                'ca' => (float) $row->ca, 'marge' => (float) $row->marge, 'direct' => (float) $direct,
                'assoc_ca' => (float) $associes->ca, 'assoc_marge' => (float) $associes->marge,
            ];
        }

        // Synthèse des associés, pour le patron uniquement.
        $associates = null;
        if ($user->hasPermission('associates.manage') && \App\Models\Fund::exists()) {
            $service = app(\App\Services\FundService::class);
            $releves = \App\Models\Fund::where('active', true)->get()->map(fn ($f) => $service->statement($f));
            $associates = [
                'count'                => $releves->count(),
                'capital_restant'      => (float) $releves->sum('capital_restant'),
                'stock_valeur'         => (float) $releves->sum('stock_valeur'),
                'benefice_a_verser'    => (float) $releves->sum('benefice_a_verser'),
                'part_patron_restante' => (float) $releves->sum('part_patron_restante'),
                'tresorerie'           => (float) $releves->sum('tresorerie'),
            ];
        }

        // Stock par type d'emplacement, détaillé par unité (cartons, pièces…) :
        // on n'additionne pas des unités différentes.
        $stockByType = $scope(StoreProduct::query()->join('stores', 'stores.id', '=', 'store_products.store_id'), 'store_products.store_id')
            ->join('products', 'products.id', '=', 'store_products.product_id')
            ->selectRaw("stores.type as type, COALESCE(products.unite, 'pcs') as unite, SUM(store_products.quantity) as qty")
            ->groupBy('stores.type', DB::raw("COALESCE(products.unite, 'pcs')"))
            ->get()
            ->groupBy('type')
            ->map(fn ($lignes) => Product::resumeQuantites($lignes));

        $pendingTransfers = \App\Models\StockTransfer::visibleTo($user)
            ->whereIn('status', [\App\Models\StockTransfer::PENDING, \App\Models\StockTransfer::APPROVED, \App\Models\StockTransfer::IN_TRANSIT])
            ->count();

        return [
            'sales'             => $sales,
            'stock_by_type'     => $stockByType,
            'pending_transfers' => $pendingTransfers,
            'locations'         => Store::visibleTo($user)->count(),
            'associates'        => $associates,
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user->isStoreScoped()) {
            // Magasin d'affectation (users.store_id) et emplacements rattachés.
            $store_id = $user->assignedStoreId();
            $scopeIds = $user->accessibleStoreIds();
            if (! $store_id) {
                // Se déconnecter avant de rediriger vers /login est indispensable :
                // sinon Authentification::login() voit un utilisateur toujours
                // authentifié et le renvoie directement vers /home, qui échoue de
                // nouveau ici — boucle de redirection infinie (ERR_TOO_MANY_REDIRECTS)
                // pour tout gérant de boutique non encore affecté à une boutique.
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('login')->with('error', 'Votre compte n\'est pas encore affecté à une boutique. Demandez à un administrateur de vous en attribuer une, puis reconnectez-vous.');
            }
            // Créances et dépenses de SON périmètre : un employé rattaché ne voit
            // pas les chiffres des autres magasins (le solde client, lui, cumule
            // les factures de tous les magasins).
            $total_credits = Facture::whereIn('store_id', $scopeIds)->where('reste', '>', 0)->sum('reste');
            $total_purchases = $this->totalPurchases($store_id);
            $total_sales = Sale::whereIn('store_id', $scopeIds)
                ->whereDate('created_at', Carbon::today())
                ->sum('prixTotal');
            $total_sales_paid = Payment::whereHas('facture', function ($q) use ($store_id) {
                $q->where('store_id', $store_id);
            })->sum('versement');
            $solde_orange_money = Payment::whereHas('facture', function ($q) use ($store_id) {
                $q->where('store_id', $store_id);
            })->where('paid_by', 'orange money')->sum('versement');
            $solde_cash = Payment::whereHas('facture', function ($q) use ($store_id) {
                $q->where('store_id', $store_id);
            })->where('paid_by', 'cash')->sum('versement');
            $total_expenses = Expense::whereIn('store_id', $scopeIds)->sum('amount');
            $total_customers = Customer::count();
            $total_quantities = Product::resumeStock($scopeIds);
            // Balance = total ventes (toutes dates confondues) - total achats - total
            // dépenses de CETTE boutique — contrairement à $total_sales (ventes du
            // jour) et $total_expenses (tout le périmètre) utilisés par ailleurs
            // sur ce dashboard. $total_purchases est déjà calculé ci-dessus
            // (combine `purchases` + `purchase_orders`, cf. totalPurchases()).
            $total_sales_all_time_store = Facture::where('store_id', $store_id)->sum('montant_total');
            $total_expenses_store = Expense::where('store_id', $store_id)->sum('amount');
            $store_balance = $total_sales_all_time_store - $total_purchases - $total_expenses_store;
            $total_purchase_invoices = Purchase::where('store_id', $store_id)->count()
                + PurchaseOrder::where('store_id', $store_id)->where('status', '!=', 'cancelled')->count();
            $total_sales_invoices = Facture::where('store_id', $store_id)->count();
            $total_third_party_interest = Sale::where('is_third_party', true)->where('store_id', $store_id)->sum('interet');
            $latestSales = Sale::where('store_id', $store_id)->with('product')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            // whereNull('deleted_at') : sinon une facture supprimée reste comptée
            // dans le chiffre d'affaires mensuel affiché.
            $salesData = DB::table('factures')
                ->whereNull('deleted_at')
                ->where('store_id', $store_id)
                ->select(DB::raw('SUM(montant_total) as total'), DB::raw('MONTH(created_at) as month'))
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $purchasesData = $this->monthlyPurchases($store_id);
            $gains = Sale::where('store_id', $store_id)->sum('interet');
            $total_gains_all = $gains;
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $sales = [];
            $purchases = [];

            foreach (range(1, 12) as $month) {
                $sales[] = $salesData[$month] ?? 0;
                $purchases[] = -($purchasesData[$month] ?? 0);
            }
        }else{
            $total_credits = Customer::where('balance', '>', 0)->sum('balance');
            $total_purchases = $this->totalPurchases();
            $total_sales = Sale::whereDate('created_at', Carbon::today())
                ->sum('prixTotal');
            $total_sales_paid = Payment::sum('versement');
            $solde_orange_money = Payment::where('paid_by', 'orange money')->sum('versement');
            $solde_cash = Payment::where('paid_by', 'cash')->sum('versement');
            $total_expenses = Expense::sum('amount');
            $total_customers = Customer::count();
            $total_quantities = Product::resumeStock();
            $total_purchase_invoices = Purchase::count() + PurchaseOrder::where('status', '!=', 'cancelled')->count();
            $total_sales_invoices = Facture::count();
            $total_third_party_interest = Sale::where('is_third_party', true)->sum('interet');
            $latestSales = Sale::with('product')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            // whereNull('deleted_at') : voir plus haut, même raison.
            $salesData = DB::table('factures')
                ->whereNull('deleted_at')
                ->select(DB::raw('SUM(montant_total) as total'), DB::raw('MONTH(created_at) as month'))
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $purchasesData = $this->monthlyPurchases();
            $gains = Sale::sum('interet');
            $total_gains_all = $gains;
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $sales = [];
            $purchases = [];

            foreach (range(1, 12) as $month) {
                $sales[] = $salesData[$month] ?? 0;
                $purchases[] = -($purchasesData[$month] ?? 0);
            }
        }
        $customers = Customer::all();
        $query = Facture::query();

        if ($request->filled('customer_id')) {
            $query->where('customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        if ($user->isStoreScoped()) {
            $query->whereIn('store_id', $user->accessibleStoreIds());
        }
        
        // Les clients sont chargés ici, en une requête : la vue les cherchait
        // un par un avec Customer::find() à l'intérieur de sa boucle.
        $factures = $query->with(['paiements', 'customer'])->get();

        $grouped = $factures->groupBy(function ($facture) {
            return $facture->customer_id . '|' . $facture->created_at->format('Y-m-d');
        });

        $salesRankingQuery = Sale::query();
        if ($user->isStoreScoped()) {
            $salesRankingQuery->whereIn('store_id', $user->accessibleStoreIds());
        }

        $topProducts = (clone $salesRankingQuery)
            ->whereYear('created_at', now()->year)
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(prixTotal) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->take(10)
            ->with('product:id,libelle')
            ->get();

        $topProductsByRevenue = (clone $salesRankingQuery)
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(prixTotal) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->with('product:id,libelle')
            ->get();

        // products.pcs (stock global mis en cache) n'est pas fiablement synchronisé avec
        // store_products (cf. achats qui ne le réalimentent jamais) : on calcule le stock
        // réel à la volée en sommant store_products.quantity plutôt que de faire confiance
        // à la colonne stockée.
        $stockAlerts = Product::withSum('stores as pcs', 'store_products.quantity')
            ->having('pcs', '<=', 100)
            ->orderBy('pcs')
            ->take(100)
            ->get(['id', 'libelle', 'unite']);

        // Uniquement calculée pour un employé rattaché à un magasin ; la vue admin
        // affiche l'équivalent par boutique via sa propre boucle @foreach(Store::all()).
        $store_balance = $store_balance ?? null;

        // Vue globale multi-emplacements (ventes par période, ventes directes
        // depuis les entrepôts, transferts en cours), limitée au périmètre.
        $overview = $this->locationOverview($user);

        return view('index', compact('overview', 'latestSales', 'total_purchases', 'total_sales', 'total_sales_paid', 'solde_orange_money', 'solde_cash', 'total_expenses', 'total_customers', 'total_quantities', 'total_purchase_invoices', 'total_sales_invoices', 'total_credits', 'gains', 'total_gains_all', 'sales', 'purchases', 'months', 'grouped', 'customers', 'topProducts', 'topProductsByRevenue', 'stockAlerts', 'total_third_party_interest', 'store_balance'));
    }
}