<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtReminder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }

    /**
     * Rappels visibles par l'utilisateur : tous pour une vue globale ; pour un
     * employé rattaché, ceux des factures de son périmètre (ou, sans facture
     * précise, ceux des clients qui ont des factures dans ce périmètre).
     */
    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, ?User $user): \Illuminate\Database\Eloquent\Builder
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        $ids = $user->accessibleStoreIds();

        return $query->where(fn ($q) => $q
            ->whereHas('facture', fn ($f) => $f->whereIn('store_id', $ids))
            ->orWhere(fn ($sans) => $sans
                ->whereNull('facture_id')
                ->whereHas('customer.factures', fn ($f) => $f->whereIn('store_id', $ids))));
    }
}
