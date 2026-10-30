<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'date_emis' => 'date',
        'date_recu' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** Fournisseur (table partners, partagée avec les ventes tierces). */
    public function supplier()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Fonds de l'associé qui finance l'achat (null = fonds propres). */
    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    /** Transport + douane + frais divers de l'arrivage. */
    public function getFraisGnfAttribute(): float
    {
        return (float) $this->transport_cost_gnf + (float) $this->customs_cost_gnf + (float) $this->other_fees_gnf;
    }

    /**
     * Prix des marchandises seules (coût de revient total moins les frais) :
     * c'est ce qui est prélevé sur le compte d'un associé qui finance l'achat.
     */
    public function getMontantMarchandisesGnfAttribute(): float
    {
        return round((float) $this->total_landed_cost_gnf - $this->frais_gnf, 2);
    }
}
