<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facture extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * Les attributs qui peuvent être assignés en masse
     */
    protected $fillable = [
        'numero_facture',
        'customer_id',
        'store_id',
        'quantity',
        'montant_total',
        'avance',
        'reste',
        'statut',
        'livraison',
        'notes',
        'order_id', // Ajouter pour la relation avec la commande
        'user_id',  // Vendeur / utilisateur qui a émis la facture
        'channel',
    ];

    /**
     * Canal d'émission. « pos » = vente flash (encaissement rapide en caisse).
     * NULL = facture antérieure à ce suivi, canal inconnu.
     */
    public const CHANNEL_POS = 'pos';
    public const CHANNEL_FACTURATION = 'facturation';
    public const CHANNEL_ECOMMERCE = 'ecommerce';
    public const CHANNEL_DEVIS = 'devis';
    public const CHANNEL_TIERS = 'tiers';

    public const CHANNELS = [
        self::CHANNEL_POS         => 'Vente flash (caisse)',
        self::CHANNEL_FACTURATION => 'Facturation',
        self::CHANNEL_ECOMMERCE   => 'Commande e-commerce',
        self::CHANNEL_DEVIS       => 'Devis validé',
        self::CHANNEL_TIERS       => 'Vente tierce',
    ];

    /**
     * Prochain numéro de facture libre (AAAAMM + séquence).
     *
     * `max('id')` hors corbeille ignorerait les factures supprimées alors que
     * l'index unique sur numero_facture, lui, les compte : on repart du
     * maximum corbeille comprise, puis on avance tant que le numéro est pris.
     */
    public static function prochainNumero(): string
    {
        $sequence = (static::withTrashed()->max('id') ?? 0) + 1;

        do {
            $numero = date('Ym') . sprintf('%04d', $sequence);
            $sequence++;
        } while (static::withTrashed()->where('numero_facture', $numero)->exists());

        return $numero;
    }

    /**
     * Accesseur pour le nom complet du client
     */
    public function getCustomerNameAttribute()
    {
        $client = $this->customer;
        if ($client) {
            return $client->customerName . '-' . $client->mark;
        }
        return 'Client inconnu';
    }

    /**
     * Relation avec la boutique (store)
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé la facture
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le client (customer)
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relation avec les paiements
     */
    public function paiements()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relation avec les ventes (sales)
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'numeroFacture', 'numero_facture');
    }

    /**
     * Quantités de la facture par unité (« 5 pièces · 2 cartons ») : la
     * colonne `quantity` additionne des unités différentes. Charger `sales`
     * avec la liste pour éviter une requête par facture.
     */
    public function getQuantiteResumeeAttribute(): string
    {
        return $this->sales->isNotEmpty()
            ? Product::resumeQuantites($this->sales, 'quantity')
            : number_format((float) $this->quantity, 0, ',', ' ');
    }

    /**
     * ============================================
     * RELATION AVEC LA COMMANDE (ORDER) - AJOUT
     * ============================================
     */
    
    /**
     * Relation avec la commande (e-commerce)
     * Si la table orders a une colonne 'invoice_number'
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'numero_facture', 'invoice_number');
    }

    /**
     * OU si vous avez une colonne order_id dans factures
     */
    // public function order()
    // {
    //     return $this->belongsTo(Order::class, 'order_id');
    // }

    /**
     * OU si vous voulez utiliser la relation inverse
     */
    // public function order()
    // {
    //     return $this->hasOne(Order::class, 'invoice_number', 'numero_facture');
    // }

    /**
     * ============================================
     * AUTRES RELATIONS UTILES
     * ============================================
     */

    /**
     * Relation avec les produits via les ventes
     */
    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            Sale::class,
            'numeroFacture',
            'id',
            'numero_facture',
            'product_id'
        );
    }

    /**
     * Montant réellement encaissé sur cette facture.
     *
     * Volontairement calculé depuis `reste`, et non depuis `statut`, `avance` ou la
     * somme des paiements — les trois ont un angle mort :
     *  - `statut` n'est écrit qu'en « payé » / « non payé » : une facture soldée à
     *    moitié y figure comme non payée ;
     *  - `avance` n'est renseigné qu'à la création de la vente, jamais mis à jour par
     *    les encaissements suivants ;
     *  - `payments.total_paye` est un cumul, pas un montant unitaire : en faire la
     *    somme compte plusieurs fois le même argent.
     *
     * `reste` est le seul champ que tiennent à jour les deux chemins, la création de
     * la vente comme l'enregistrement d'un paiement.
     */
    public function montantEncaisse(): float
    {
        return max(0, (float) $this->montant_total - (float) $this->reste);
    }

    /**
     * La facture a-t-elle reçu de l'argent, en totalité ou en partie ?
     *
     * Trois conditions cumulées là où une seule suffirait aujourd'hui : elles
     * concordent sur les données actuelles, et le cumul garantit qu'un futur chemin
     * qui n'en mettrait qu'une à jour ne rouvrira pas la porte à la suppression d'un
     * encaissement.
     */
    public function aRecuUnPaiement(): bool
    {
        return $this->montantEncaisse() > 0
            || (float) $this->avance > 0
            || $this->paiements()->where('versement', '>', 0)->exists();
    }

    /**
     * Vérifier si la facture est intégralement payée
     */
    public function isPaid(): bool
    {
        return (float) $this->montant_total > 0
            && $this->montantEncaisse() >= (float) $this->montant_total;
    }

    /**
     * Vérifier si la facture est partiellement payée
     */
    public function isPartial(): bool
    {
        return $this->aRecuUnPaiement() && ! $this->isPaid();
    }

    /**
     * Vérifier si la facture est livrée
     */
    public function isDelivered(): bool
    {
        return $this->livraison === 'livré';
    }

    /**
     * Obtenir le montant total payé
     */
    public function getTotalPaidAttribute()
    {
        return $this->paiements()->sum('versement');
    }

    /**
     * Obtenir le montant restant à payer
     */
    public function getRemainingAttribute()
    {
        return $this->montant_total - $this->getTotalPaidAttribute();
    }
}