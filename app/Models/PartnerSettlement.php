<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Règlement fait à un partenaire pour les produits pris chez lui (ventes tierces). */
class PartnerSettlement extends Model
{
    public const MODES = [
        'cash'         => 'Espèces',
        'orange_money' => 'Orange Money',
        'virement'     => 'Virement',
        'marchandise'  => 'Échange de marchandise',
    ];

    protected $fillable = ['partner_id', 'amount', 'paid_at', 'mode', 'note', 'user_id'];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getModeLabelAttribute(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }
}
