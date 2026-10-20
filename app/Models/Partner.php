<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address', 'notes'];

    /**
     * Un partenaire est un fournisseur : il approvisionne les achats
     * (purchase_orders.partner_id) comme les ventes tierces (sales.partner_id).
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'partner_id');
    }

    /** Lignes vendues avec un produit pris chez ce partenaire. */
    public function thirdPartySales()
    {
        return $this->hasMany(Sale::class, 'partner_id')->where('is_third_party', true);
    }

    public function settlements()
    {
        return $this->hasMany(PartnerSettlement::class);
    }

    /**
     * Ce que le vendeur doit au partenaire, tous temps confondus : prix payé
     * au partenaire × quantité, sur les factures non supprimées.
     */
    public static function montantsDus(): \Illuminate\Support\Collection
    {
        return Sale::query()
            ->join('factures', 'factures.numero_facture', '=', 'sales.numeroFacture')
            ->whereNull('factures.deleted_at')
            ->where('sales.is_third_party', true)
            ->whereNotNull('sales.partner_id')
            ->selectRaw('sales.partner_id, SUM(sales.purchase_price_from_partner * sales.quantity) as du')
            ->groupBy('sales.partner_id')
            ->pluck('du', 'partner_id');
    }

    /** Règlements faits à chaque partenaire, tous temps confondus. */
    public static function montantsRegles(): \Illuminate\Support\Collection
    {
        return PartnerSettlement::query()
            ->selectRaw('partner_id, SUM(amount) as regle')
            ->groupBy('partner_id')
            ->pluck('regle', 'partner_id');
    }
}