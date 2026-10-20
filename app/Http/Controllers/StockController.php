<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stock par emplacement : vue globale (patron), fiche d'un emplacement,
 * journal des mouvements, traçabilité d'un produit, inventaire / ajustement.
 * Tout est limité au périmètre de l'utilisateur (Store::visibleTo).
 */
class StockController extends Controller
{
    public function __construct(private StockService $stock)
    {
        $this->middleware('auth.check');
        $this->middleware('permission:stock.view')->only(['index', 'show']);
        $this->middleware('permission:stock.movements')->only(['movements', 'trace']);
        $this->middleware('permission:stock.adjust')->only(['adjustForm', 'adjust']);
    }

    /** Vue globale : un emplacement par ligne, chiffres calculés à la volée. */
    public function index(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();

        $stores = Store::visibleTo($user)
            ->with('parent:id,store_name')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderByDesc('is_main')->orderBy('type')->orderBy('store_name')
            ->get();
        $ids = $stores->pluck('id');

        // Quelques requêtes agrégées pour tous les emplacements, pas une par ligne.
        $stock = StoreProduct::whereIn('store_id', $ids)
            ->selectRaw('store_id, SUM(quantity) as qty, COUNT(CASE WHEN quantity > 0 THEN 1 END) as products')
            ->groupBy('store_id')->get()->keyBy('store_id');

        $value = DB::table('store_products')
            ->join('products', 'products.id', '=', 'store_products.product_id')
            ->whereIn('store_products.store_id', $ids)
            ->where('store_products.quantity', '>', 0)
            ->selectRaw('store_products.store_id, SUM(store_products.quantity * COALESCE(products.price_sale, 0)) as cost')
            ->groupBy('store_products.store_id')->pluck('cost', 'store_id');

        // Quantités en stock et vendues aujourd'hui, par unité : cartons et
        // pièces ne s'additionnent pas.
        $stockUnites = DB::table('store_products')
            ->join('products', 'products.id', '=', 'store_products.product_id')
            ->whereIn('store_products.store_id', $ids)
            ->selectRaw("store_products.store_id, COALESCE(products.unite, 'pcs') as unite, SUM(store_products.quantity) as qty")
            ->groupBy('store_products.store_id', DB::raw("COALESCE(products.unite, 'pcs')"))
            ->get();
        $ventesUnites = Sale::whereIn('store_id', $ids)
            ->whereBetween('created_at', [$today, $today->copy()->endOfDay()])
            ->selectRaw("store_id, COALESCE(unite, 'pcs') as unite, SUM(quantity) as qty")
            ->groupBy('store_id', DB::raw("COALESCE(unite, 'pcs')"))
            ->get();

        $salesToday = Sale::whereIn('store_id', $ids)
            ->whereBetween('created_at', [$today, $today->copy()->endOfDay()])
            ->selectRaw('store_id, SUM(prixTotal) as amount, SUM(quantity) as qty')
            ->groupBy('store_id')->get()->keyBy('store_id');

        $rows = $stores->map(fn ($s) => (object) [
            'store'        => $s,
            'qty'          => (int) ($stock[$s->id]->qty ?? 0),
            'products'     => (int) ($stock[$s->id]->products ?? 0),
            'value'        => (float) ($value[$s->id] ?? 0),
            'sales_today'  => (float) ($salesToday[$s->id]->amount ?? 0),
            'qty_today'    => (int) ($salesToday[$s->id]->qty ?? 0),
            'qty_unites'   => Product::resumeQuantites($stockUnites->where('store_id', $s->id)),
            'ventes_unites'=> Product::resumeQuantites($ventesUnites->where('store_id', $s->id)),
        ]);

        $totals = [
            'stock'          => $rows->sum('qty'),
            'stock_magasins' => $rows->filter(fn ($r) => $r->store->type !== Store::TYPE_ENTREPOT)->sum('qty'),
            'stock_entrepots'=> $rows->filter(fn ($r) => $r->store->type === Store::TYPE_ENTREPOT)->sum('qty'),
            'sales_today'    => $rows->sum('sales_today'),
            'direct_today'   => $rows->filter(fn ($r) => $r->store->type === Store::TYPE_ENTREPOT)->sum('sales_today'),
            'value'          => $rows->sum('value'),
            'stock_unites'          => Product::resumeQuantites($stockUnites),
            'stock_magasins_unites' => Product::resumeQuantites($stockUnites->whereIn('store_id', $stores->where('type', '!=', Store::TYPE_ENTREPOT)->pluck('id'))),
            'stock_entrepots_unites'=> Product::resumeQuantites($stockUnites->whereIn('store_id', $stores->where('type', Store::TYPE_ENTREPOT)->pluck('id'))),
        ];

        return view('stock.index', compact('rows', 'totals'));
    }

    /** Détail d'un emplacement : stock par produit, ventes, derniers mouvements. */
    public function show(Request $request, Store $store)
    {
        $this->authorizeStore($store->id);
        $store->load(['parent', 'children', 'users:id,name,store_id', 'manager:id,name']);

        $products = StoreProduct::where('store_id', $store->id)
            ->with('product:id,libelle,sku,price,price_sale,unite,pcs_par_carton,pcs_par_paquet,dimension_rouleau')
            ->when($request->filled('q'), fn ($q) => $q->whereHas('product', fn ($p) => $p
                ->where('libelle', 'like', '%' . $request->input('q') . '%')
                ->orWhere('sku', 'like', '%' . $request->input('q') . '%')))
            ->when(! $request->boolean('all'), fn ($q) => $q->where('quantity', '!=', 0))
            ->orderByDesc('quantity')
            ->paginate(50)->appends($request->query());

        $periods = [
            'Aujourd\'hui'   => [Carbon::today(), Carbon::today()->endOfDay()],
            'Cette semaine'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'Ce mois'        => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'Cette année'    => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
        ];
        $sales = collect($periods)->map(function ($range) use ($store) {
            $s = Sale::where('store_id', $store->id)
                ->whereBetween('created_at', $range)
                ->selectRaw('COALESCE(SUM(prixTotal),0) as amount, COALESCE(SUM(quantity),0) as qty, COALESCE(SUM(interet),0) as margin')
                ->first();
            // Quantités vendues par unité (« 5 pièces · 2 cartons »).
            $s->qty_unites = Product::resumeQuantites(Sale::where('store_id', $store->id)
                ->whereBetween('created_at', $range)
                ->selectRaw("COALESCE(unite, 'pcs') as unite, SUM(quantity) as qty")
                ->groupBy(DB::raw("COALESCE(unite, 'pcs')"))->get());

            return $s;
        });

        $movements = StockMovement::where('store_id', $store->id)
            ->with(['product:id,libelle', 'user:id,name', 'counterpartStore:id,store_name'])
            ->latest('id')->limit(20)->get();

        return view('stock.show', compact('store', 'products', 'sales', 'movements'));
    }

    /** Journal des mouvements (lecture seule). */
    public function movements(Request $request)
    {
        $user = auth()->user();

        $query = StockMovement::visibleTo($user)
            ->with(['product:id,libelle,sku', 'store:id,store_name', 'counterpartStore:id,store_name', 'user:id,name', 'customer:id,customerName', 'supplier:id,name']);

        foreach (['product_id', 'store_id', 'type', 'user_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('document_ref')) {
            $query->where('document_ref', 'like', '%' . $request->input('document_ref') . '%');
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->input('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->input('date_fin'));
        }

        $movements = $query->latest('id')->paginate(50)->appends($request->query());
        $stores = Store::optionsFor($user, false);
        $products = Product::orderBy('libelle')->get(['id', 'libelle', 'sku', 'unite', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau']);
        $users = User::whereIn('id', StockMovement::visibleTo($user)->distinct()->pluck('user_id')->filter())->orderBy('name')->get(['id', 'name']);

        return view('stock.movements', compact('movements', 'stores', 'products', 'users'));
    }

    /**
     * Parcours d'un produit : achat (fournisseur) → emplacement → transferts →
     * vente (client, vendeur, prix), dans l'ordre chronologique.
     */
    public function trace(Request $request)
    {
        $user = auth()->user();
        $products = Product::orderBy('libelle')->get(['id', 'libelle', 'sku', 'unite', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau']);
        $product = $request->filled('product_id') ? Product::find($request->input('product_id')) : null;
        $movements = collect();
        $balances = collect();

        if ($product) {
            $movements = StockMovement::visibleTo($user)
                ->where('product_id', $product->id)
                ->when($request->filled('document_ref'), fn ($q) => $q->where('document_ref', 'like', '%' . $request->input('document_ref') . '%'))
                ->with(['store:id,store_name,type', 'counterpartStore:id,store_name', 'user:id,name', 'customer:id,customerName', 'supplier:id,name'])
                ->orderBy('id')
                ->paginate(100)->appends($request->query());

            $balances = StoreProduct::where('product_id', $product->id)
                ->whereIn('store_id', Store::visibleTo($user)->pluck('id'))
                ->where('quantity', '!=', 0)
                ->with('store:id,store_name,type,parent_id')
                ->get();
        }

        return view('stock.trace', compact('products', 'product', 'movements', 'balances'));
    }

    public function adjustForm(Request $request)
    {
        $stores = Store::optionsFor(auth()->user());
        $products = Product::orderBy('libelle')->get(['id', 'libelle', 'sku', 'unite', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau']);
        $funds = \App\Models\Fund::active()->orderBy('name')->get();
        // Stock enregistré par emplacement, produit et propriétaire : affiché
        // pendant le comptage pour voir l'écart avant d'enregistrer.
        $stockEnregistre = StoreProduct::whereIn('store_id', $stores->pluck('id'))
            ->get(['store_id', 'product_id', 'fund_id', 'quantity'])
            ->mapWithKeys(fn ($r) => [$r->store_id.'|'.$r->product_id.'|'.($r->fund_id ?? '') => (int) $r->quantity]);

        return view('stock.adjust', compact('stores', 'products', 'funds', 'stockEnregistre'));
    }

    /**
     * Inventaire / ajustement : on saisit la quantité comptée, le système
     * enregistre l'écart comme un mouvement, avec l'auteur et le motif.
     */
    public function adjust(Request $request)
    {
        $data = $request->validate([
            'store_id'   => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'counted'    => 'required|integer|min:0',
            'type'       => 'required|in:' . StockMovement::INVENTORY . ',' . StockMovement::ADJUSTMENT,
            'reason'     => 'required|string|min:3|max:500',
            'fund_id'    => 'nullable|exists:funds,id',
        ], [
            'reason.required' => 'Le motif est obligatoire : il est conservé dans le journal.',
        ]);

        $this->authorizeStore($data['store_id']);

        // Stock compté pour un seul propriétaire (fonds propres ou associé).
        $fundId = ! empty($data['fund_id']) ? (int) $data['fund_id'] : null;
        $before = $this->stock->availableForFund((int) $data['store_id'], (int) $data['product_id'], $fundId);

        $movement = DB::transaction(fn () => $this->stock->adjustTo((int) $data['store_id'], (int) $data['product_id'], (int) $data['counted'], [
            'type'        => $data['type'],
            'source_type' => 'inventory',
            'reason'      => $data['reason'],
            'fund_id'     => $fundId,
            // Écart valorisé au coût de l'associé : perte ou gain partagé selon sa clé.
            'unit_price'  => $fundId ? app(\App\Services\FundService::class)->unitCost($fundId, (int) $data['product_id']) : null,
        ]));

        // Quantités dans l'unité du produit : « 40 → 38 cartons (écart −2 cartons) ».
        $produit = Product::find($data['product_id']);
        $ecart = $data['counted'] - $before;
        $message = $movement
            ? sprintf('Stock ajusté pour « %s » : %s → %s (écart %s%s), tracé dans le journal.',
                $produit->libelle,
                number_format($before, 0, ',', ' '),
                $produit->quantiteAvecUnite($data['counted']),
                $ecart >= 0 ? '+' : '−',
                $produit->quantiteAvecUnite(abs($ecart)))
            : 'Aucun écart : le stock compté correspond au stock enregistré.';

        return redirect()->route('stock.adjust.form')->with('success', $message);
    }
}
