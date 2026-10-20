<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory, Concerns\FigeUniteProduit;

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const IN_TRANSIT = 'in_transit';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    public const STATUSES = [
        self::PENDING    => 'En attente d\'autorisation',
        self::APPROVED   => 'Autorisé',
        self::IN_TRANSIT => 'En transit',
        self::COMPLETED  => 'Réceptionné',
        self::REJECTED   => 'Refusé',
        self::CANCELLED  => 'Annulé',
    ];

    protected $fillable = [
        'reference', 'product_id', 'from_store_id', 'to_store_id', 'quantity', 'unite', 'status', 'fund_id',
        'requested_by', 'approved_by', 'shipped_by', 'received_by',
        'approved_at', 'shipped_at', 'received_at', 'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'shipped_at'  => 'datetime',
        'received_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Transferts dont la source ou la destination est visible par l'utilisateur. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        $ids = $user->accessibleStoreIds();

        return $query->where(fn ($q) => $q->whereIn('from_store_id', $ids)->orWhereIn('to_store_id', $ids));
    }
}
