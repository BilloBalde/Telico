<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CurrencySetting;
use App\Models\Partner;
use App\Models\Place;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\User;
use App\Services\PurchaseOrderCostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        // Route::resource('purchase-orders', …) n'appliquait aucune permission.
        $this->middleware('permission:purchases.view')->only(['index', 'show']);
        $this->middleware('permission:purchases.create')->only(['create', 'store']);
        $this->middleware('permission:purchases.edit')->only(['edit', 'update']);
        $this->middleware('permission:purchases.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['store', 'items', 'supplier']);

        // Un employé rattaché à un magasin ne voit que les achats reçus dans son périmètre.
        $user = auth()->user();
        if ($user->isStoreScoped()) {
            $query->whereIn('store_id', $user->accessibleStoreIds());
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->input('partner_id'));
        }

        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->input('reference') . '%');
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->filled('origin')) {
            $query->where('origin', $request->input('origin'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $dataTable = $query->latest()->paginate(20)->appends($request->query());
        $stores = Store::optionsFor($user, false);

        return view('purchase_orders.index', compact('dataTable', 'stores'));
    }

    /**
     * "PurchaseOrder::count() + 1" se déraille dès qu'une commande est supprimée : le
     * compte total baisse, le prochain numéro généré retombe sur une référence déjà
     * prise par une commande existante, et la validation "unique" la rejette ("cette
     * référence existe déjà"). On se base à la place sur le plus grand numéro déjà
     * utilisé aujourd'hui, immunisé contre les trous laissés par des suppressions.
     */
    private function nextReference(): string
    {
        $prefix = 'PO-' . Carbon::now()->format('Ymd') . '-';

        $lastNumber = PurchaseOrder::where('reference', 'like', $prefix . '%')
            ->pluck('reference')
            ->map(fn ($reference) => (int) substr($reference, strlen($prefix)))
            ->max() ?? 0;

        return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * "reçu" seulement si la date de réception est passée (ou aujourd'hui) — avant,
     * le statut était figé à "received" dès la création, même quand la marchandise
     * n'était pas encore arrivée. Recalculé aussi à la modification, pour rester
     * cohérent quand la date de réception change.
     */
    private function resolveStatus(?string $dateRecu): string
    {
        if (! $dateRecu) {
            return 'pending';
        }

        return Carbon::parse($dateRecu)->lte(Carbon::today()) ? 'received' : 'pending';
    }

    /**
     * Coût total estimé d'un achat avant enregistrement (prix × quantité,
     * converti si achat en devise, + transport, douane et frais) : sert à
     * vérifier que le capital non investi de l'associé le couvre.
     */
    private function estimatedTotal(array $validated): float
    {
        $rate = $validated['origin'] === 'chine' ? (float) ($validated['exchange_rate_used'] ?? 1) : 1.0;
        $lines = collect($validated['lines'])->sum(fn ($l) => (float) ($l['unit_price_foreign'] ?? 0) * $rate * (int) $l['quantity']);

        return $lines + (float) ($validated['transport_cost_gnf'] ?? 0) + (float) ($validated['customs_cost_gnf'] ?? 0) + (float) ($validated['other_fees_gnf'] ?? 0);
    }

    /** Refuse un achat d'associé qui dépasserait ses apports non investis. */
    private function assertFundCovers(?int $fundId, float $total, ?int $exceptOrderId = null): void
    {
        if (! $fundId) {
            return;
        }

        $disponible = app(\App\Services\FundService::class)->capitalAvailable($fundId, $exceptOrderId);
        if ($total > $disponible + 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fund_id' => sprintf(
                    'Capital insuffisant pour %s : %s FG disponibles (apports non investis), achat estimé à %s FG.',
                    \App\Models\Fund::labelFor($fundId),
                    number_format($disponible, 0, ',', ' '),
                    number_format($total, 0, ',', ' ')
                ),
            ]);
        }
    }

    public function create()
    {
        $stores = Store::optionsFor(auth()->user());
        $suppliers = Partner::orderBy('name')->get();
        $funds = \App\Models\Fund::active()->orderBy('name')->get();
        $products = Product::orderBy('libelle')->get(['id', 'libelle', 'sku', 'unite', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau']);
        $currencies = CurrencySetting::whereNotNull('rate_to_gnf')->where('currencyCode', '!=', 'GNF')->get();
        $reference = $this->nextReference();
        // Pour les modales "Nouvelle boutique" / "Nouveau produit" du formulaire.
        $places = Place::all();
        $storeManagers = User::where('id', '>', 2)->get();
        $categories = Category::orderBy('category_type')->get();

        return view('purchase_orders.create', compact('stores', 'products', 'currencies', 'reference', 'places', 'storeManagers', 'categories', 'suppliers', 'funds'));
    }

    public function store(Request $request, PurchaseOrderCostingService $costingService)
    {
        $validated = $request->validate([
            'reference' => 'required|string|unique:purchase_orders,reference',
            'store_id' => 'required|exists:stores,id',
            'partner_id' => 'nullable|exists:partners,id',
            // Financé par : vide = fonds propres, sinon le fonds d'un associé.
            'fund_id' => 'nullable|exists:funds,id',
            'origin' => 'required|in:chine,guinee',
            // La table s'appelle `currency_settings` (pas `currencies` : ce nom n'existe
            // pas en base et faisait planter la validation dès qu'une devise était
            // choisie, avec une SQLSTATE "table doesn't exist").
            'currency_code' => 'nullable|string|exists:currency_settings,currencyCode',
            // Obligatoire dès qu'une devise est choisie : le taux n'est plus pré-rempli
            // automatiquement côté formulaire, l'utilisateur doit le saisir lui-même.
            'exchange_rate_used' => 'nullable|required_with:currency_code|numeric|min:0',
            'transport_cost_gnf' => 'nullable|numeric|min:0',
            'customs_cost_gnf' => 'nullable|numeric|min:0',
            'other_fees_gnf' => 'nullable|numeric|min:0',
            'date_emis' => 'required|date',
            'date_recu' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.unit_price_foreign' => 'nullable|numeric|min:0',
            'lines.*.cbm_per_carton' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
            'lines.*.cartons' => 'nullable|integer|min:0',
            'lines.*.pieces_per_carton' => 'nullable|integer|min:0',
            'lines.*.quantity' => 'required|integer|min:1',
        ], [
            'reference.unique' => 'Cette référence existe déjà.',
            'exchange_rate_used.required_with' => 'Le taux du jour est obligatoire quand une devise est sélectionnée.',
        ]);

        // La destination doit appartenir au périmètre de l'utilisateur.
        $this->authorizeStore($validated['store_id']);
        $this->assertFundCovers($validated['fund_id'] ?? null, $this->estimatedTotal($validated));

        $order = PurchaseOrder::create([
            'reference' => $validated['reference'],
            'store_id' => $validated['store_id'],
            'partner_id' => $validated['partner_id'] ?? null,
            'fund_id' => $validated['fund_id'] ?? null,
            'created_by' => auth()->id(),
            'origin' => $validated['origin'],
            'currency_code' => $validated['origin'] === 'chine' ? $validated['currency_code'] : null,
            // 'exchange_rate_used' => $validated['origin'] === 'chine' ? $validated['exchange_rate_used'] : null,
            'exchange_rate_used' => $validated['exchange_rate_used'] ?? 1,   // taux par défaut 1
            'transport_cost_gnf' => $validated['transport_cost_gnf'] ?? 0,
            'customs_cost_gnf' => $validated['customs_cost_gnf'] ?? 0,
            'other_fees_gnf' => $validated['other_fees_gnf'] ?? 0,
            'status' => $this->resolveStatus($validated['date_recu'] ?? null),
            'date_emis' => $validated['date_emis'],
            'date_recu' => $validated['date_recu'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $costingService->costOrder($order, $validated['lines']);

        return redirect()->route('purchase-orders.show', $order->id)
            ->with('success', 'Achat enregistré, coût de revient et stock mis à jour avec succès.');
    }

    public function show($id)
    {
        $order = PurchaseOrder::with(['store', 'items.product', 'supplier', 'creator'])->findOrFail($id);
        $this->authorizeStore($order->store_id);

        return view('purchase_orders.show', compact('order'));
    }

    public function edit($id)
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);
        $this->authorizeStore($order->store_id);
        $stores = Store::optionsFor(auth()->user());
        $suppliers = Partner::orderBy('name')->get();
        $funds = \App\Models\Fund::orderBy('name')->get();
        $products = Product::orderBy('libelle')->get(['id', 'libelle', 'sku', 'unite', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau']);
        $currencies = CurrencySetting::whereNotNull('rate_to_gnf')->where('currencyCode', '!=', 'GNF')->get();
        // Pour les modales "Nouvelle boutique" / "Nouveau produit" du formulaire.
        $places = Place::all();
        $storeManagers = User::where('id', '>', 2)->get();
        $categories = Category::orderBy('category_type')->get();

        return view('purchase_orders.edit', compact('order', 'stores', 'products', 'currencies', 'places', 'storeManagers', 'categories', 'suppliers', 'funds'));
    }

    public function update(Request $request, $id, PurchaseOrderCostingService $costingService)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cet achat est annulé, il ne peut plus être modifié.');
        }

        $this->authorizeStore($order->store_id);

        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'partner_id' => 'nullable|exists:partners,id',
            'fund_id' => 'nullable|exists:funds,id',
            'origin' => 'required|in:chine,guinee',
            'currency_code' => 'nullable|string|exists:currency_settings,currencyCode',
            // Obligatoire dès qu'une devise est choisie : le taux n'est plus pré-rempli
            // automatiquement côté formulaire, l'utilisateur doit le saisir lui-même.
            'exchange_rate_used' => 'nullable|required_with:currency_code|numeric|min:0',
            'transport_cost_gnf' => 'nullable|numeric|min:0',
            'customs_cost_gnf' => 'nullable|numeric|min:0',
            'other_fees_gnf' => 'nullable|numeric|min:0',
            'date_emis' => 'required|date',
            'date_recu' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price_foreign' => 'nullable|numeric|min:0',
            'lines.*.cbm_per_carton' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
            'lines.*.cartons' => 'nullable|integer|min:0',
            'lines.*.pieces_per_carton' => 'nullable|integer|min:0',
        ], [
            'exchange_rate_used.required_with' => 'Le taux du jour est obligatoire quand une devise est sélectionnée.',
        ]);

        $this->authorizeStore($validated['store_id']);
        $this->assertFundCovers($validated['fund_id'] ?? null, $this->estimatedTotal($validated), $order->id);
        $previousStoreId = (int) $order->store_id;
        $previousFundId = $order->fund_id;

        $order->update([
            'store_id' => $validated['store_id'],
            'partner_id' => $validated['partner_id'] ?? null,
            'fund_id' => $validated['fund_id'] ?? null,
            'origin' => $validated['origin'],
            'currency_code' => $validated['origin'] === 'chine' ? $validated['currency_code'] : null,
            ///'exchange_rate_used' => $validated['origin'] === 'chine' ? $validated['exchange_rate_used'] : null,
            'exchange_rate_used' => $validated['exchange_rate_used'] ?? 1,
            'transport_cost_gnf' => $validated['transport_cost_gnf'] ?? 0,
            'customs_cost_gnf' => $validated['customs_cost_gnf'] ?? 0,
            'other_fees_gnf' => $validated['other_fees_gnf'] ?? 0,
            'date_emis' => $validated['date_emis'],
            'date_recu' => $validated['date_recu'] ?? null,
            'status' => $this->resolveStatus($validated['date_recu'] ?? null),
            'notes' => $validated['notes'] ?? null,
        ]);

        $costingService->costOrder($order, $validated['lines'], $previousStoreId, $previousFundId);

        return redirect()->route('purchase-orders.show', $order->id)
            ->with('success', 'Achat mis à jour avec succès.');
    }

    public function destroy($id)
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cet achat est déjà annulé.');
        }

        \DB::transaction(function () use ($order) {
            // Retire du stock exactement ce que cet achat y avait ajouté.
            // (Le prix de revient du produit n'est pas "annulé" — s'il a été mis à jour
            // par un achat plus récent depuis, revenir en arrière serait incorrect.)
            $stock = app(\App\Services\StockService::class);
            foreach ($order->items as $item) {
                $stock->forceDecrement((int) $order->store_id, (int) $item->product_id, (int) $item->quantity, [
                    'type'         => \App\Models\StockMovement::PURCHASE_CANCEL,
                    'source_type'  => 'purchase_order',
                    'source_id'    => $order->id,
                    'document_ref' => $order->reference,
                    'supplier_id'  => $order->partner_id,
                    'fund_id'      => $order->fund_id,
                    'reason'       => 'Annulation de l\'achat ' . $order->reference,
                ]);
            }

            $order->update(['status' => 'cancelled']);
        });

        return redirect()->route('purchase-orders.index')->with('success', 'Achat annulé, stock ajusté en conséquence.');
    }
}
