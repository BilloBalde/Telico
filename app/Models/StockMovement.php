<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Ligne du journal des mouvements de stock. Écrite uniquement par
 * StockService. Le journal est en ajout seul : toute correction passe par un
 * nouveau mouvement (type CORRECTION), jamais par la modification ou la
 * suppression d'une ligne existante.
 */
class StockMovement extends Model
{
    use Concerns\FigeUniteProduit;

    public const PURCHASE = 'PURCHASE';
    public const PURCHASE_CANCEL = 'PURCHASE_CANCEL';
    public const SALE = 'SALE';
    public const SALE_RETURN = 'RETURN';
    public const TRANSFER_OUT = 'TRANSFER_OUT';
    public const TRANSFER_IN = 'TRANSFER_IN';
    public const ADJUSTMENT = 'ADJUSTMENT';
    public const INVENTORY = 'INVENTORY';
    public const CORRECTION = 'CORRECTION';
    public const INITIAL = 'INITIAL';

    public const LABELS = [
        self::PURCHASE        => 'Achat',
        self::PURCHASE_CANCEL => 'Annulation d\'achat',
        self::SALE            => 'Vente',
        self::SALE_RETURN     => 'Retour / annulation de vente',
        self::TRANSFER_OUT    => 'Transfert sortant',
        self::TRANSFER_IN     => 'Transfert entrant',
        self::ADJUSTMENT      => 'Ajustement',
        self::INVENTORY       => 'Inventaire',
        self::CORRECTION      => 'Correction',
        self::INITIAL         => 'Stock initial',
    ];

    protected $fillable = [
        'product_id', 'store_id', 'type', 'quantity', 'unite', 'stock_before', 'stock_after',
        'counterpart_store_id', 'source_type', 'source_id', 'document_ref',
        'customer_id', 'supplier_id', 'unit_price', 'user_id', 'reason', 'fund_id',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Le journal des mouvements de stock ne peut pas être modifié : enregistrez un mouvement de correction.');
        });

        static::deleting(function () {
            throw new LogicException('Le journal des mouvements de stock ne peut pas être supprimé : enregistrez un mouvement de correction.');
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function counterpartStore()
    {
        return $this->belongsTo(Store::class, 'counterpart_store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function supplier()
    {
        return $this->belongsTo(Partner::class, 'supplier_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        return $query->whereIn('store_id', $user->accessibleStoreIds());
    }
}
