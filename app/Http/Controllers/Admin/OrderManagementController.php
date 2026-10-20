<?php
// app/Http/Controllers/Admin/OrderManagementController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Facture;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Notifications\OrderStatusNotification;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderManagementController extends Controller
{
    public function __construct(private StockService $stock)
    {
        // Les écrans ne vérifiaient que « est un employé » : n'importe quel
        // vendeur de n'importe quelle boutique voyait toutes les commandes.
        $this->middleware('permission:orders.view')->only(['index', 'confirmed', 'factures', 'show', 'stockCheck', 'getFactureItems']);
        $this->middleware('permission:orders.assign')->only(['assign']);
    }

    /**
     * Commande hors du périmètre : une commande non attribuée reste au siège
     * (profils à vue globale) ; une commande attribuée n'est visible que de sa
     * boutique et du siège.
     */
    private function authorizeOrder(?Order $order): void
    {
        if (! $order || ! $order->isVisibleTo(auth()->user())) {
            abort(403, 'Cette commande est traitée par une autre boutique ou par le siège.');
        }
    }

    // Affiche la liste des commandes en attente

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Order::with(['user', 'items.product', 'store', 'assignedBy'])
            ->visibleTo($user)
            ->where('status', 'pending')
            ->latest();

        if ($request->input('attribution') === 'siege') {
            $query->whereNull('store_id');
        } elseif ($request->input('attribution') === 'boutiques') {
            $query->whereNotNull('store_id');
        }

        $orders = $query->paginate(10)->appends($request->query());

        // Pour l'attribution : chaque section, et pour chaque commande de la
        // page, les sections qui ont tout le stock (toutes lignes confondues).
        $sections = collect();
        $couverture = [];
        if ($user->hasPermission('orders.assign') && ! $user->isRestrictedToStore()) {
            $sections = \App\Models\Store::sectionsWithIds();
            $productIds = $orders->getCollection()->flatMap(fn ($o) => $o->items->pluck('product_id'))->unique();
            $stock = DB::table('store_products')->whereIn('product_id', $productIds)
                ->selectRaw('store_id, product_id, SUM(quantity) as qty')
                ->groupBy('store_id', 'product_id')->get()->groupBy('product_id');

            foreach ($orders as $order) {
                foreach ($sections as $section) {
                    $couverture[$order->id][$section->id] = $order->items->every(fn ($item) => (int) ($stock->get($item->product_id, collect())
                        ->whereIn('store_id', $section->section_ids)->sum('qty')) >= $item->quantity);
                }
            }
        }

        return view('admin.orders.index', compact('orders', 'sections', 'couverture'));
    }

    /**
     * Attribue une commande en attente à une boutique (section), ou la rend au
     * siège (store_id vide). Les employés de la boutique sont prévenus.
     */
    public function assign(Order $order, Request $request)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Seule une commande en attente peut être attribuée.');
        }

        $data = $request->validate(['store_id' => 'nullable|exists:stores,id']);
        $storeId = $data['store_id'] ?? null;

        $order->update([
            'store_id'    => $storeId,
            'assigned_by' => $storeId ? auth()->id() : null,
            'assigned_at' => $storeId ? now() : null,
        ]);

        if (! $storeId) {
            return back()->with('success', "Commande #{$order->id} reprise par le siège.");
        }

        $store = \App\Models\Store::find($storeId);
        $equipe = \App\Models\User::whereIn('store_id', \App\Models\Store::withDescendantIds($storeId))->get()
            ->filter(fn ($u) => $u->hasPermission('orders.view'));
        \Illuminate\Support\Facades\Notification::send($equipe, new \App\Notifications\OrderAssignedToStoreNotification($order, $store));

        return back()->with('success', "Commande #{$order->id} attribuée à {$store->store_name} ({$equipe->count()} employé(s) prévenu(s)).");
    }

    /**
     * La boutique rend une commande au siège (stock insuffisant, livraison
     * impossible…). La commande redevient « traitée au siège », disparaît de
     * la boutique, et les responsables du siège sont prévenus.
     */
    public function release(Order $order, Request $request)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'pending' || ! $order->store_id) {
            return back()->with('error', 'Seule une commande en attente attribuée à une boutique peut être rendue au siège.');
        }

        $boutique = $order->store?->store_name ?? 'la boutique';
        $motif = trim((string) $request->input('reason'));

        $order->update(['store_id' => null, 'assigned_by' => null, 'assigned_at' => null]);

        $siege = \App\Models\User::whereNotNull('role_id')->get()
            ->filter(fn ($u) => ! $u->isCustomer() && $u->hasPermission('orders.assign'));
        \Illuminate\Support\Facades\Notification::send($siege, new \App\Notifications\OrderReturnedToHeadOfficeNotification($order, $boutique, auth()->user()->name, $motif));

        return redirect()->route('admin.orders.index')->with('success', "Commande #{$order->id} rendue au siège.");
    }

    public function stockCheck(Order $order, Request $request)
    {
        $this->authorizeOrder($order);
        $order->load('items.product');
        // Par défaut, la boutique à qui la commande est attribuée.
        $storeId = $request->input('store_id') ?: $order->store_id;
        $locations = (array) $request->input('locations', []);
        // Seuls les emplacements du périmètre de l'utilisateur sont proposés.
        $stores = \App\Models\Store::visibleTo(auth()->user())->orderBy('store_name')->get(['id', 'store_name', 'type']);
        $productIds = $order->items->pluck('product_id');

        // Une seule requête pour tous les produits/boutiques plutôt que N×M requêtes,
        // par propriétaire : fonds propres, ou fonds d'un associé.
        $stockRows = DB::table('store_products')
            ->whereIn('product_id', $productIds)
            ->selectRaw('store_id, product_id, fund_id, SUM(quantity) as quantity')
            ->groupBy('store_id', 'product_id', 'fund_id')
            ->get()
            ->groupBy('product_id');

        $items = [];
        $allOk = true;

        foreach ($order->items as $item) {
            $product   = $item->product;
            // Le stock qui compte est celui de la boutique qui va réellement livrer la
            // commande, pas un total global toutes boutiques confondues — sinon l'aperçu
            // peut dire "OK" pour une boutique qui n'a en réalité rien en stock.
            // Emplacement de la ligne : celui choisi pour cet article, sinon
            // l'emplacement par défaut de la commande.
            // Clé d'emplacement : « magasin » (fonds propres) ou « magasin|fonds ».
            $lineKey = $locations[$item->id] ?? ($storeId ? (string) $storeId : null);
            [$lineStoreId, $lineFundId] = $this->parseLocation($lineKey);
            $available = $lineStoreId
                ? $this->stock->availableForFund($lineStoreId, $item->product_id, $lineFundId)
                : $this->stock->totalAvailable($item->product_id);
            $ok        = $available >= $item->quantity;

            $rowsForProduct = $stockRows->get($item->product_id, collect());
            $perStore = collect();
            foreach ($stores as $store) {
                $suffixe = $store->type === \App\Models\Store::TYPE_ENTREPOT ? ' (entrepôt)' : '';
                // Fonds propres : toujours proposé, même à 0.
                $perStore->push([
                    'store_id'   => (string) $store->id,
                    'store_name' => $store->store_name . $suffixe,
                    'available'  => (int) ($rowsForProduct->first(fn ($r) => (int) $r->store_id === $store->id && ! $r->fund_id)->quantity ?? 0),
                ]);
                // Fonds d'associés : seulement s'il y a du stock.
                foreach ($rowsForProduct->filter(fn ($r) => (int) $r->store_id === $store->id && $r->fund_id && $r->quantity > 0) as $r) {
                    $perStore->push([
                        'store_id'   => $store->id . '|' . $r->fund_id,
                        'store_name' => $store->store_name . $suffixe . ' — ' . \App\Models\Fund::labelFor((int) $r->fund_id),
                        'available'  => (int) $r->quantity,
                    ]);
                }
            }
            $items[] = [
                'item_id'   => $item->id,
                'store_id'  => $lineKey,
                'name'      => $product->libelle ?? 'Produit #' . $product->id,
                'requested' => $item->quantity,
                // Unité de la ligne pour les libellés (« 3 cartons demandés »).
                'unite'     => \App\Models\Product::motUnite($item->unite ?: $product->unite, 2),
                'available' => $available,
                'ok'        => $ok,
                'per_store' => $perStore,
            ];
            if (!$ok) $allOk = false;
        }

        return response()->json(['all_ok' => $allOk, 'items' => $items]);
    }
    /**
     * « 12 » = magasin 12, fonds propres ; « 12|3 » = magasin 12, fonds de
     * l'associé n° 3. @return array{0:int|null, 1:int|null}
     */
    private function parseLocation(?string $key): array
    {
        if (! $key) {
            return [null, null];
        }
        [$store, $fund] = array_pad(explode('|', $key, 2), 2, null);

        return [(int) $store ?: null, (int) $fund ?: null];
    }

    // Affiche toutes les ventes confirmées par le manager
    public function confirmed(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'facture', 'sales', 'store'])
            ->visibleTo(auth()->user())
            ->whereIn('status', ['approved', 'delivering', 'delivered'])
            ->latest();

        if ($request->filled('order_id')) {
            $query->where('id', $request->order_id);
        }
        if ($request->filled('client')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%'.$request->client.'%'));
        }
        if ($request->filled('payment')) {
            $query->whereHas('facture', fn($q) => $q->where('statut', $request->payment));
        }

        $orders = $query->paginate(15);
        return view('admin.orders.confirmed', compact('orders'));
    }

  

    // Rejette une commande
    public function reject(Order $order)
    {
        $this->authorizeOrder($order);
        if ($order->status !== 'pending') {

            if (request()->ajax()) {
                return response()->json(['error' => 'Déjà traitée'], 400);
            }

            return redirect()->back()->with('error', 'Cette commande a déjà été traitée.');
        }

        $order->update(['status' => 'rejected']);

        $order->user?->notify(new OrderStatusNotification($order, 'rejected'));

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.orders.index')->with('success', 'Commande rejetée.');
    }

    public function approve(Order $order, Request $request)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'pending') {
            return $this->approveFailure($request, 'Commande déjà traitée.');
        }

        // Magasin qui facture : celui choisi, sinon la boutique attribuée.
        $storeId = $request->input('store_id') ?: $order->store_id;
        $orderItems = $order->items;
        if ($orderItems->isEmpty()) {
            return $this->approveFailure($request, 'Cette commande ne contient aucun article.');
        }

        // Emplacement source de chaque article : choisi ligne par ligne (à défaut,
        // la boutique indiquée). Chaque emplacement doit appartenir au périmètre
        // de l'utilisateur — contrôlé ici, pas seulement à l'écran.
        $locations = (array) $request->input('locations', []);
        $lineStores = [];
        $lineFunds = [];
        foreach ($orderItems as $item) {
            [$lineStore, $lineFund] = $this->parseLocation(($locations[$item->id] ?? null) ?: ($storeId ? (string) $storeId : null));
            $lineFunds[$item->id] = $lineFund;
            if (! $lineStore) {
                $nom = $item->product?->libelle ?? 'Produit #' . $item->product_id;
                return $this->approveFailure($request, "Choisissez l'emplacement du stock pour « {$nom} ».");
            }
            $lineStores[$item->id] = $lineStore;
        }

        // Magasin qui facture : celui choisi, sinon celui de l'emplacement du
        // premier article (un entrepôt facture pour son magasin de rattachement).
        if (! $storeId) {
            $storeId = \App\Models\Store::find(reset($lineStores))?->pointDeVenteId();
        }
        foreach (array_unique(array_merge([(int) $storeId], array_values($lineStores))) as $candidate) {
            if (! \App\Models\Store::whereKey($candidate)->exists()) {
                return $this->approveFailure($request, 'Emplacement inconnu.');
            }
            if (! auth()->user()->canAccessStore($candidate)) {
                return $this->approveFailure($request, "Vous n'êtes pas autorisé à puiser dans cet emplacement.");
            }
        }

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            // Tous les articles sont vérifiés avant la moindre écriture : un seul
            // manquant et la commande entière reste en attente, avec le détail.
            $this->stock->assertAvailable($orderItems->map(fn ($item) => [
                'store_id'   => $lineStores[$item->id],
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'fund_id'    => $lineFunds[$item->id],
            ])->all());
            $funds = app(\App\Services\FundService::class);

            // Décrémentation du stock (boutique + global) et création des lignes de vente
            $totalQuantity = 0;
            $totalInteret  = 0;

            foreach ($orderItems as $item) {
                $qty = $item->quantity;
                $product = $item->product ?? \App\Models\Product::find($item->product_id);

                // Décrémente le stock de la boutique choisie (et le compteur global avec) —
                // avant, seul le compteur global était touché, donc le stock réel de la
                // boutique n'était jamais mis à jour par une vente e-commerce.
                $lineStoreId = $lineStores[$item->id];
                $this->stock->decrementOrFail($lineStoreId, $item->product_id, $qty, [
                    'type'         => \App\Models\StockMovement::SALE,
                    'source_type'  => 'order',
                    'source_id'    => $order->id,
                    'document_ref' => $invoiceNumber,
                    'unit_price'   => $item->price,
                    'fund_id'      => $lineFunds[$item->id],
                ]);
                $item->update(['store_id' => $lineStoreId, 'fund_id' => $lineFunds[$item->id]]);

                // Intérêt basé sur le dernier prix d'achat
                // Même prix de revient que le point de vente. Auparavant, le
                // dernier « achat simple » (table purchases) faisait foi : un vieux
                // prix au carton donnait des intérêts négatifs, et sans achat
                // simple tout le prix de vente était compté comme intérêt.
                $fundId       = $lineFunds[$item->id];
                $coutFonds    = $fundId ? $funds->unitCost($fundId, (int) $item->product_id) : null;
                $interet      = $fundId
                    ? ($coutFonds > 0 ? ((float) $item->price - $coutFonds) * $qty : 0)
                    : ($product ? $product->interetPour((float) $item->price, (int) $qty) : 0);
                $totalInteret += $interet;

                Sale::create([
                    'numeroFacture' => $invoiceNumber,
                    'product_id'    => $item->product_id,
                    'store_id'      => $lineStoreId,
                    'quantity'      => $qty,
                    'prix'          => $item->price,
                    'prixTotal'     => $qty * $item->price,
                    'interet'       => $interet,
                    'user_id'       => auth()->id(),
                    'fund_id'       => $fundId,
                    'unit_cost'     => $fundId ? $coutFonds : null,
                ]);
                $totalQuantity += $qty;
            }

            // Mettre à jour le solde de la boutique (intérêts e-commerce)
            if ($totalInteret != 0) {
                \App\Models\Store::where('id', $storeId)->increment('balance', $totalInteret);
            }

            // Création client, facture, paiement... (gardez votre code existant)
            $user = $order->user;
            // withTrashed : un client archivé garde son `mark`, et l'index unique
            // qui porte dessus ignore `deleted_at`. Sans cela, la recherche ne
            // trouvait rien, l'insertion se heurtait à l'index, et l'approbation
            // échouait sur une erreur SQL brute — un client archivé ne pouvait
            // plus jamais repasser commande.
            $customer = Customer::withTrashed()->firstOrCreate(
                ['email' => $user->email],
                [
                    'mark'         => strtoupper(substr($user->name, 0, 3)) . $user->id,
                    'customerName' => $user->name,
                    'tel'          => $user->phone ?? 'N/A',
                    'address'      => $order->address?->full_address ?? 'N/A',
                    'email'        => $user->email,
                ]
            );

            // Il repasse commande : on le sort de la corbeille, avec son historique.
            if ($customer->trashed()) {
                $customer->restore();
            }

            $isPaid = $order->payment_status === 'paid';
            $avance = $isPaid ? $order->total_amount : 0;
            $reste = $order->total_amount - $avance;
            $statut = $isPaid ? 'payé' : 'non payé';
            $paidBy = $order->payment_method === 'orange_money' ? 'orange money' : 'cash';

            $facture = Facture::create([
                'numero_facture' => $invoiceNumber,
                'customer_id'    => $customer->id,
                'store_id'       => $storeId,
                'user_id'        => auth()->id(),
                'channel'        => Facture::CHANNEL_ECOMMERCE,
                'quantity'       => $totalQuantity,
                'montant_total'  => $order->total_amount,
                'avance'         => $avance,
                'reste'          => $reste,
                'statut'         => $statut,
                'livraison'      => 'non livré',
                'notes'          => 'Commande e-commerce #' . $order->id,
            ]);

            if ($avance > 0) {
                Payment::create([
                    'facture_id' => $facture->id,
                    'versement'  => $avance,
                    'total_paye' => $avance,
                    'reste'      => 0,
                    'paid_by'    => $paidBy,
                    'note'       => 'Paiement ' . $order->payment_method . ' – Transaction: ' . ($order->transaction_id ?? 'N/A'),
                ]);
            }

            $order->update([
                'status' => 'approved',
                'invoice_number' => $invoiceNumber,
            ]);

            // Le client n'est connu qu'ici : on complète les mouvements écrits
            // quelques lignes plus haut, dans cette même transaction (ce n'est
            // pas une réécriture de l'historique : rien n'est encore validé).
            DB::table('stock_movements')
                ->where('document_ref', $invoiceNumber)
                ->whereNull('customer_id')
                ->update(['customer_id' => $customer->id]);

            // L'approbation cree une facture : sans ce recalcul, les compteurs du
            // client ignoraient la commande. Le solde restait inchange pendant que
            // la dette reelle augmentait du montant de la vente.
            $customer->recalculerCompteurs();

            DB::commit();

            $order->user?->notify(new OrderStatusNotification($order, 'approved'));

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true]);
            }
            return redirect()->route('admin.orders.index')->with('success', 'Commande approuvée, vente enregistrée.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->approveFailure($request, 'Erreur : ' . $e->getMessage());
        }
    }

    private function approveFailure(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('error', $message);
    }

    /**
     * Récupère les articles d'une facture (pour livraison partielle)
     */
    public function getFactureItems(Facture $facture)
    {
        $order = $facture->order;
        if ($order) {
            $this->authorizeOrder($order);
        }

        if (!$order) {
            return response()->json(['error' => 'Commande introuvable pour cette facture.'], 404);
        }

        $order->load('items.product');

        $items = $order->items->map(fn($item) => [
            'id'                 => $item->id,
            'product_name'       => $item->product->libelle ?? 'Produit #' . $item->product_id,
            'quantity'           => $item->quantity,
            'quantity_delivered' => $item->quantity_delivered ?? 0,
            'quantity_remaining' => max(0, $item->quantity - ($item->quantity_delivered ?? 0)),
            // Unité de la ligne (« cartons »), pour les quantités de la fenêtre de livraison.
            'unite'              => \App\Models\Product::motUnite($item->unite ?: $item->product?->unite, 2),
        ]);

        return response()->json($items);
    }

    /**
     * Enregistre une livraison partielle
     */
    public function partialDeliver(Request $request, Facture $facture)
    {
        $order = $facture->order;
        if ($order) {
            $this->authorizeOrder($order);
        }

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Commande introuvable.'], 404);
        }

        try {
            $quantities = $request->input('quantities', []);

            foreach ($quantities as $itemId => $newQty) {
                $item = OrderItem::where('id', $itemId)
                    ->where('order_id', $order->id)
                    ->first();

                if (!$item) continue;

                // Additionner la nouvelle livraison à ce qui était déjà livré, sans dépasser le total commandé
                $item->quantity_delivered = min(
                    $item->quantity_delivered + (int) $newQty,
                    $item->quantity
                );
                $item->save();
            }

            // Requête fraîche après les mises à jour (évite le cache Eloquent)
            $totalDelivered = $order->items()->sum('quantity_delivered');
            $totalOrdered   = $order->items()->sum('quantity');

            if ($totalOrdered > 0 && $totalDelivered >= $totalOrdered) {
                $facture->update(['livraison' => 'livré']);

                if ($order->status !== 'delivered') {
                    $order->update(['status' => 'delivered']);
                    $order->user?->notify(new OrderStatusNotification($order, 'delivered'));
                }
            } elseif ($totalDelivered > 0) {
                $facture->update(['livraison' => 'partiellement livré']);

                // Notifier "en cours de livraison" une seule fois (le statut sert de garde)
                if (! in_array($order->status, ['delivering', 'delivered'])) {
                    $order->update(['status' => 'delivering']);
                    $order->user?->notify(new OrderStatusNotification($order, 'delivering'));
                }
            } else {
                $facture->update(['livraison' => 'non livré']);
            }

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Liste des factures générées par approbation de commandes e-commerce
     */
    public function factures(Request $request)
    {
        $query = Facture::with(['customer', 'store', 'paiements', 'sales:id,numeroFacture,product_id,quantity,unite', 'order.user', 'order.items.product'])
            ->whereHas('order', fn ($q) => $q->visibleTo(auth()->user()))
            ->where('numero_facture', 'like', 'INV-%')
            ->latest();

        if ($request->filled('numero_facture')) {
            $query->where('numero_facture', 'like', '%' . $request->numero_facture . '%');
        }
        if ($request->filled('client')) {
            $query->whereHas('order.user', fn($q) => $q->where('name', 'like', '%' . $request->client . '%'));
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('livraison')) {
            $query->where('livraison', $request->livraison);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $factures = $query->paginate(15);
        return view('admin.orders.factures', compact('factures'));
    }

    /**
     * Afficher les détails d'une commande spécifique
     */
    public function show(Order $order)
    {
        // Charge les relations nécessaires pour la vue détail
        $this->authorizeOrder($order);
        $order->load(['user', 'items.product', 'address', 'facture', 'store', 'assignedBy']);

        return view('admin.orders.show', compact('order'));
    }

}