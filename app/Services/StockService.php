<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\StockMovement;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

/**
 * Seul point d'écriture du stock par emplacement (store_products).
 *
 * Chaque variation écrit une ligne dans le journal stock_movements. Le
 * paramètre $context décrit le mouvement :
 *   type                 StockMovement::SALE, PURCHASE, TRANSFER_OUT…
 *   source_type/source_id document métier (sale, purchase_order, stock_transfer…)
 *   document_ref         référence lisible (n° de facture, d'achat, de transfert)
 *   customer_id, supplier_id, unit_price, counterpart_store_id, reason
 *   user_id              par défaut l'utilisateur connecté
 *
 * Les appelants travaillent dans une transaction : si une ligne échoue, tout
 * est annulé, mouvements du journal compris.
 *
 * Fonds : le stock d'un emplacement est séparé par propriétaire. $context
 * ['fund_id'] désigne le fonds de l'associé concerné ; absent ou null =
 * fonds propres. Une sortie ne puise que dans le fonds indiqué.
 */
class StockService
{
    /**
     * Décrémente le stock d'un emplacement s'il y en a assez (opération
     * atomique : jamais de stock négatif, même en accès concurrent).
     */
    public function decrementIfAvailable(int $storeId, int $productId, int $quantity, array $context = []): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        $rowId = $this->fundRows($storeId, $productId, $context['fund_id'] ?? null)
            ->where('quantity', '>=', $quantity)
            ->orderBy('id')
            ->lockForUpdate()
            ->value('id');

        if (! $rowId) {
            return false;
        }

        $affected = DB::table('store_products')
            ->where('id', $rowId)
            ->where('quantity', '>=', $quantity)
            ->decrement('quantity', $quantity);

        if ($affected === 0) {
            return false;
        }

        $this->decrementGlobalStock($productId, $quantity);
        $this->record($storeId, $productId, -$quantity, $context + ['type' => StockMovement::ADJUSTMENT]);

        return true;
    }

    /**
     * Comme decrementIfAvailable(), mais lève une exception au message
     * explicite (produit, emplacement, disponible, demandé).
     */
    public function decrementOrFail(int $storeId, int $productId, int $quantity, array $context = []): void
    {
        if (! $this->decrementIfAvailable($storeId, $productId, $quantity, $context)) {
            $fundId = $context['fund_id'] ?? null;
            throw new InsufficientStockException([
                $this->shortageMessage($storeId, $productId, $this->availableForFund($storeId, $productId, $fundId), $quantity, $fundId),
            ]);
        }
    }

    /**
     * Vérifie en une passe que toutes les lignes sont servies, AVANT la
     * moindre écriture. Les lignes portant sur le même produit au même
     * emplacement sont cumulées. Lève une exception listant toutes les lignes
     * en défaut (pas seulement la première).
     *
     * @param  array<int, array{store_id:int, product_id:int, quantity:int, fund_id?:int|null}>  $lines
     */
    public function assertAvailable(array $lines): void
    {
        $needed = [];
        foreach ($lines as $line) {
            $key = (int) $line['store_id'] . ':' . (int) $line['product_id'] . ':' . (int) ($line['fund_id'] ?? 0);
            $needed[$key] = ($needed[$key] ?? 0) + (int) $line['quantity'];
        }

        if (! $needed) {
            return;
        }

        $storeIds = array_unique(array_map(fn ($k) => (int) explode(':', $k)[0], array_keys($needed)));
        $productIds = array_unique(array_map(fn ($k) => (int) explode(':', $k)[1], array_keys($needed)));

        $available = DB::table('store_products')
            ->whereIn('store_id', $storeIds)
            ->whereIn('product_id', $productIds)
            ->selectRaw('store_id, product_id, COALESCE(fund_id, 0) as fund, SUM(quantity) as qty')
            ->groupBy('store_id', 'product_id', DB::raw('COALESCE(fund_id, 0)'))
            ->get()
            ->mapWithKeys(fn ($r) => [$r->store_id . ':' . $r->product_id . ':' . (int) $r->fund => (int) $r->qty]);

        $failures = [];
        foreach ($needed as $key => $qty) {
            $have = $available[$key] ?? 0;
            if ($have < $qty) {
                [$storeId, $productId, $fundId] = array_map('intval', explode(':', $key));
                $failures[] = $this->shortageMessage($storeId, $productId, $have, $qty, $fundId ?: null);
            }
        }

        if ($failures) {
            throw new InsufficientStockException($failures);
        }
    }

    /**
     * Stock total d'un produit, tous emplacements confondus. Calculé à la volée à partir
     * de store_products (la seule table alimentée de façon fiable par ventes/achats/
     * transferts) plutôt que lu depuis products.pcs, qui n'est pas gardé synchronisé par
     * les achats et dérive donc de la réalité au fil du temps.
     */
    public function totalAvailable(int $productId): int
    {
        return (int) DB::table('store_products')->where('product_id', $productId)->sum('quantity');
    }

    /**
     * Stock d'un produit dans un emplacement, pour un seul propriétaire
     * (null = fonds propres).
     */
    public function availableForFund(int $storeId, int $productId, ?int $fundId): int
    {
        return (int) $this->fundRows($storeId, $productId, $fundId)->sum('quantity');
    }

    /** Lignes de stock d'un emplacement / produit / fonds. */
    private function fundRows(int $storeId, int $productId, ?int $fundId)
    {
        return DB::table('store_products')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->when($fundId, fn ($q) => $q->where('fund_id', $fundId), fn ($q) => $q->whereNull('fund_id'));
    }

    /**
     * Stock d'un produit dans un emplacement précis, tous fonds confondus.
     */
    public function availableInStore(int $storeId, int $productId): int
    {
        return (int) DB::table('store_products')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    public function increment(int $storeId, int $productId, int $quantity, array $context = []): void
    {
        if ($quantity <= 0) {
            return;
        }

        $row = StoreProduct::firstOrCreate(
            ['store_id' => $storeId, 'product_id' => $productId, 'fund_id' => $context['fund_id'] ?? null],
            ['quantity' => 0]
        );

        $row->increment('quantity', $quantity);
        $this->incrementGlobalStock($productId, $quantity);
        $this->record($storeId, $productId, $quantity, $context + ['type' => StockMovement::ADJUSTMENT]);
    }

    /**
     * Retire du stock sans contrôle de disponibilité (annulation d'un achat
     * dont une partie a déjà été vendue, par exemple) : le stock peut alors
     * devenir négatif, ce qui signale une incohérence à régulariser plutôt
     * que de la masquer. Toujours tracé.
     */
    public function forceDecrement(int $storeId, int $productId, int $quantity, array $context = []): void
    {
        if ($quantity <= 0) {
            return;
        }

        $row = StoreProduct::firstOrCreate(
            ['store_id' => $storeId, 'product_id' => $productId, 'fund_id' => $context['fund_id'] ?? null],
            ['quantity' => 0]
        );

        $row->decrement('quantity', $quantity);
        $this->decrementGlobalStock($productId, $quantity);
        $this->record($storeId, $productId, -$quantity, $context + ['type' => StockMovement::CORRECTION]);
    }

    /**
     * Inventaire / ajustement : fixe le stock compté. Le mouvement enregistré
     * est l'écart (compté − théorique). Retourne null si aucun écart.
     */
    public function adjustTo(int $storeId, int $productId, int $counted, array $context = []): ?StockMovement
    {
        $counted = max(0, $counted);
        $current = $this->availableForFund($storeId, $productId, $context['fund_id'] ?? null);
        $delta = $counted - $current;

        if ($delta === 0) {
            return null;
        }

        $context += ['type' => StockMovement::INVENTORY];

        if ($delta > 0) {
            $this->increment($storeId, $productId, $delta, $context);
        } else {
            $this->forceDecrement($storeId, $productId, -$delta, $context);
        }

        return StockMovement::where('store_id', $storeId)->where('product_id', $productId)->latest('id')->first();
    }

    private function record(int $storeId, int $productId, int $signedQuantity, array $context): void
    {
        $fundId = $context['fund_id'] ?? null;
        // Stock avant / après du propriétaire concerné, pas de tout l'emplacement.
        $after = $this->availableForFund($storeId, $productId, $fundId);

        StockMovement::create([
            'product_id'           => $productId,
            'store_id'             => $storeId,
            'fund_id'              => $fundId,
            'type'                 => $context['type'],
            'quantity'             => $signedQuantity,
            'stock_before'         => $after - $signedQuantity,
            'stock_after'          => $after,
            'counterpart_store_id' => $context['counterpart_store_id'] ?? null,
            'source_type'          => $context['source_type'] ?? null,
            'source_id'            => $context['source_id'] ?? null,
            'document_ref'         => $context['document_ref'] ?? null,
            'customer_id'          => $context['customer_id'] ?? null,
            'supplier_id'          => $context['supplier_id'] ?? null,
            'unit_price'           => $context['unit_price'] ?? null,
            'user_id'              => $context['user_id'] ?? auth()->id(),
            'reason'               => $context['reason'] ?? null,
        ]);
    }

    private function shortageMessage(int $storeId, int $productId, int $available, int $requested, ?int $fundId = null): string
    {
        $fiche = DB::table('products')->where('id', $productId)->first(['libelle', 'unite']);
        $product = $fiche->libelle ?? ('Produit #' . $productId);
        // Quantités dans l'unité du produit : « Disponible : 10 cartons ».
        $unite = $fiche->unite ?? 'pcs';
        $store = DB::table('stores')->where('id', $storeId)->value('store_name') ?? ('Emplacement #' . $storeId);
        if ($fundId) {
            $store .= ' (' . (DB::table('funds')->where('id', $fundId)->value('name') ?? 'fonds #' . $fundId) . ')';
        }

        return sprintf(
            'Stock insuffisant pour %s à %s. Disponible : %s. Demandé : %s.',
            $product,
            $store,
            \App\Models\Product::quantiteEnUnite($available, $unite),
            \App\Models\Product::quantiteEnUnite($requested, $unite)
        );
    }

    private function decrementGlobalStock(int $productId, int $quantity): void
    {
        if (! DB::getSchemaBuilder()->hasColumn('products', 'pcs')) {
            return;
        }

        DB::table('products')
            ->where('id', $productId)
            ->decrement('pcs', $quantity);
    }

    private function incrementGlobalStock(int $productId, int $quantity): void
    {
        if (! DB::getSchemaBuilder()->hasColumn('products', 'pcs')) {
            return;
        }

        DB::table('products')
            ->where('id', $productId)
            ->increment('pcs', $quantity);
    }

    /**
     * Decrement cartons count if available, by StoreProduct row id.
     */
    public function decrementCtnsByRowIdIfAvailable(int $storeProductId, int $ctns): bool
    {
        if ($ctns <= 0) {
            return true;
        }

        $affected = StoreProduct::where('id', $storeProductId)
            ->where('ctns', '>=', $ctns)
            ->decrement('ctns', $ctns);

        return $affected > 0;
    }

    /**
     * Decrement quantity (PCS) if available, by StoreProduct row id.
     */
    public function decrementQtyByRowIdIfAvailable(int $storeProductId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        $row = StoreProduct::find($storeProductId);

        $affected = StoreProduct::where('id', $storeProductId)
            ->where('quantity', '>=', $quantity)
            ->decrement('quantity', $quantity);

        if ($affected > 0 && $row) {
            $this->record((int) $row->store_id, (int) $row->product_id, -$quantity, ['type' => StockMovement::ADJUSTMENT, 'fund_id' => $row->fund_id]);
        }

        return $affected > 0;
    }
}
