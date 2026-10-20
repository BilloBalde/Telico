<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionFacture extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Versements visibles par l'utilisateur : tous pour une vue globale, sinon
     * ceux qui ont réglé des factures de son périmètre. Un ancien versement,
     * non rattaché à ses factures, reste visible si le client a des factures
     * dans ce périmètre.
     */
    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, ?User $user): \Illuminate\Database\Eloquent\Builder
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        $ids = $user->accessibleStoreIds();

        return $query->where(fn ($q) => $q
            ->whereHas('payments.facture', fn ($f) => $f->whereIn('store_id', $ids))
            ->orWhere(fn ($ancien) => $ancien
                ->whereDoesntHave('payments')
                ->whereHas('customer.factures', fn ($f) => $f->whereIn('store_id', $ids))));
    }

    /** Paiements par facture créés par ce versement (factures réglées). */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /** Solde du client avant le versement (colonne `balance`). */
    public function getSoldeAvantAttribute(): float
    {
        return (float) $this->balance;
    }

    /**
     * Solde du client après le versement. Enregistré depuis octobre 2026 ;
     * pour les versements plus anciens, déduit du solde avant.
     */
    public function getSoldeApresAttribute(): float
    {
        return $this->balance_apres !== null
            ? (float) $this->balance_apres
            : max(0.0, (float) $this->balance - (float) $this->versement);
    }
}
