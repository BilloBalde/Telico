<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use Concerns\FigeUniteProduit;

    protected $fillable = ['order_id', 'product_id', 'store_id', 'fund_id', 'quantity', 'unite', 'price', 'quantity_delivered'];

    /** Emplacement qui a fourni la ligne, choisi à l'approbation. */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
