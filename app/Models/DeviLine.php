<?php
// app/Models/DeviLine.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviLine extends Model
{
    use HasFactory, Concerns\FigeUniteProduit;

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    protected static function booted()
    {
        static::creating(function ($line) {
            if (auth()->check() && empty($line->tenant_id)) {
                // No tenant needed
            }
        });
    }

    public function devis()
    {
        return $this->belongsTo(Devi::class, 'devis_id');
    }

    /** Emplacement source de la ligne (null = emplacement du devis). */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedUnitPriceAttribute()
    {
        return number_format($this->unit_price, 0, ',', ' ') . ' FG';
    }

    public function getFormattedTotalPriceAttribute()
    {
        return number_format($this->total_price, 0, ',', ' ') . ' FG';
    }
}