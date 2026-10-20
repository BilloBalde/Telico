<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Product extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'promo_start_date' => 'date',
        'promo_end_date' => 'date',
    ];

    /**
     * Unité unique du produit, choisie à la création : le stock, les achats,
     * les ventes et les factures sont tous comptés dans cette unité.
     */
    public const UNITES = [
        'pcs'      => ['libelle' => 'Pièce',    'pluriel' => 'pièces',    'court' => 'pcs'],
        'douzaine' => ['libelle' => 'Douzaine', 'pluriel' => 'douzaines', 'court' => 'douz.'],
        'carton'   => ['libelle' => 'Carton',   'pluriel' => 'cartons',   'court' => 'ctn'],
        'paquet'   => ['libelle' => 'Paquet',   'pluriel' => 'paquets',   'court' => 'paq.'],
        'rouleau'  => ['libelle' => 'Rouleau',  'pluriel' => 'rouleaux',  'court' => 'rlx'],
    ];

    public function getUniteAttribute($valeur): string
    {
        return isset(self::UNITES[$valeur]) ? $valeur : 'pcs';
    }

    /** Contenu d'une unité : « 12 pcs », « 24 pcs », « 100 m », ou null. */
    public function contenanceUnite(): ?string
    {
        return match ($this->unite) {
            'douzaine' => '12 pcs',
            'carton'   => $this->pcs_par_carton ? $this->pcs_par_carton.' pcs' : null,
            'paquet'   => $this->pcs_par_paquet ? $this->pcs_par_paquet.' pcs' : null,
            'rouleau'  => $this->dimension_rouleau ?: null,
            default    => null,
        };
    }

    /** « Carton (24 pcs) », « Rouleau (100 m) », « Pièce ». */
    public function getUniteLibelleAttribute(): string
    {
        $contenance = $this->contenanceUnite();

        return self::UNITES[$this->unite]['libelle'].($contenance ? ' ('.$contenance.')' : '');
    }

    /** Nom de l'unité en minuscules, accordé : « carton » / « cartons ». */
    public static function motUnite(?string $code, float|int $quantite = 1): string
    {
        $unite = self::UNITES[$code] ?? self::UNITES['pcs'];

        return abs($quantite) > 1 ? $unite['pluriel'] : mb_strtolower($unite['libelle']);
    }

    /** « 3 cartons », « 1 rouleau », « 12 pièces ». */
    public static function quantiteEnUnite(float|int|null $quantite, ?string $code): string
    {
        $quantite = (float) $quantite;

        return number_format($quantite, 0, ',', ' ').' '.self::motUnite($code, $quantite);
    }

    /**
     * Total de plusieurs produits, sans additionner des unités différentes :
     * [unité => quantité] devient « 12 cartons · 30 pièces · 2 rouleaux ».
     * Accepte aussi une liste de lignes ayant `unite` et une quantité.
     */
    public static function resumeQuantites(iterable $lignes, string $champ = 'qty'): string
    {
        $parUnite = [];
        foreach ($lignes as $cle => $ligne) {
            [$unite, $quantite] = is_scalar($ligne)
                ? [$cle, $ligne]
                : [data_get($ligne, 'unite'), data_get($ligne, $champ)];
            $unite = isset(self::UNITES[$unite]) ? $unite : 'pcs';
            $parUnite[$unite] = ($parUnite[$unite] ?? 0) + (float) $quantite;
        }

        $morceaux = [];
        foreach (array_keys(self::UNITES) as $unite) {
            if (! empty($parUnite[$unite])) {
                $morceaux[] = self::quantiteEnUnite($parUnite[$unite], $unite);
            }
        }

        return $morceaux ? implode(' · ', $morceaux) : '0';
    }

    /**
     * Stock total par unité, éventuellement limité à des emplacements :
     * « 1 675 pièces · 40 cartons · 25 douzaines ».
     */
    public static function resumeStock(iterable|null $storeIds = null): string
    {
        $lignes = \Illuminate\Support\Facades\DB::table('store_products')
            ->join('products', 'products.id', '=', 'store_products.product_id')
            ->when($storeIds !== null, fn ($q) => $q->whereIn('store_products.store_id', collect($storeIds)->all()))
            ->selectRaw("COALESCE(products.unite, 'pcs') as unite, SUM(store_products.quantity) as qty")
            ->groupBy(\Illuminate\Support\Facades\DB::raw("COALESCE(products.unite, 'pcs')"))
            ->get();

        return self::resumeQuantites($lignes);
    }

    /** Quantité de CE produit dans son unité : « 3 cartons ». */
    public function quantiteAvecUnite(float|int|null $quantite): string
    {
        return self::quantiteEnUnite($quantite, $this->unite);
    }

    /**
     * Une promo peut être configurée à l'avance ou laissée expirer sans être
     * désactivée manuellement — on vérifie donc la fenêtre de dates à chaque
     * lecture plutôt que de se fier uniquement au booléen is_promo.
     */
    public function isPromoActive(): bool
    {
        if (!$this->is_promo || !$this->promo_price) {
            return false;
        }

        $today = Carbon::today();

        if ($this->promo_start_date && $today->lt($this->promo_start_date)) {
            return false;
        }

        if ($this->promo_end_date && $today->gt($this->promo_end_date)) {
            return false;
        }

        return true;
    }

    public function getEffectivePromoPriceAttribute(): ?float
    {
        return $this->isPromoActive() ? (float) $this->promo_price : null;
    }

    /** Image neutre affichée pour un produit sans photo (ou photo introuvable). */
    public const IMAGE_PAR_DEFAUT = 'assets/img/produit-sans-photo.svg';

    /**
     * URL de la photo du produit, ou de l'image neutre : les produits créés
     * sans photo (fiche, caisse, achat) n'affichent plus d'image cassée.
     */
    public static function urlImage(?string $image): string
    {
        return $image && is_file(public_path('products/' . $image))
            ? asset('products/' . $image)
            : asset(self::IMAGE_PAR_DEFAUT);
    }

    public function getImageUrlAttribute(): string
    {
        return self::urlImage($this->image);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_products');
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_products')->withPivot('quantity', 'fund_id');
    }

    public function ligneCommande(){
        return $this->hasMany(LigneCommande::class);
    }
    
    /**
     * Coût d'achat unitaire (prix de revient), règle unique pour toutes les
     * ventes : point de vente, facturation, commandes en ligne, devis.
     *
     * price_sale est synchronisé à chaque achat (ancien flux Purchase et module
     * PurchaseOrder) : c'est la source la plus fiable et la plus à jour.
     * L'ancienne chaîne LigneCommande/Purchase ne sert qu'en dernier recours,
     * pour un produit jamais touché par un achat synchronisé. 0 = coût inconnu.
     */
    public function coutUnitaire(): float
    {
        if ($this->price_sale !== null && (float) $this->price_sale > 0) {
            return (float) $this->price_sale;
        }

        $latest = $this->latestLigneCommande;
        if ($latest && $latest->unit_price_purchase !== null) {
            return (float) $latest->unit_price_purchase;
        }
        if ($latest && $latest->quantity > 0 && $latest->total_price_purchase !== null) {
            return (float) ($latest->total_price_purchase / $latest->quantity);
        }

        $purchasePrice = Purchase::where('product_id', $this->id)->latest()->value('price');
        if ($purchasePrice !== null) {
            return (float) $purchasePrice;
        }

        return 0.0;
    }

    /**
     * Intérêt d'une ligne vendue : (prix − coût) × quantité, ou 0 si le coût
     * est inconnu — on ne compte pas tout le prix de vente comme bénéfice.
     */
    public function interetPour(float $prixUnitaire, int $quantite): float
    {
        $cout = $this->coutUnitaire();

        return $cout > 0 ? ($prixUnitaire - $cout) * $quantite : 0.0;
    }

    /**
     * Prix plancher de vente par unité : le plus haut du prix de vente minimum
     * fixé par le gérant et du prix de revient. Règle unique pour la caisse, la
     * facturation et les devis. $cout : coût du propriétaire de la marchandise
     * (fonds d'un associé), sinon le prix de revient du produit.
     *
     * @return array{0: float, 1: string} [plancher, 'minimum' | 'revient'] ; 0 si rien n'est connu.
     */
    public function plancherDeVente(?float $cout = null): array
    {
        $cout = $cout ?? $this->coutUnitaire();
        $minimum = (float) ($this->price_sale_minimum ?? 0);

        return $minimum > 0 && $minimum >= $cout ? [$minimum, 'minimum'] : [max(0.0, (float) $cout), 'revient'];
    }

    /**
     * Refuse un prix unitaire sous le plancher de vente (message pour le vendeur).
     *
     * @throws \RuntimeException
     */
    public function verifierPrixDeVente(float $prix, ?float $cout = null): void
    {
        [$plancher, $origine] = $this->plancherDeVente($cout);
        if ($plancher <= 0 || $prix >= $plancher) {
            return;
        }

        $fg = fn ($n) => number_format((float) $n, 0, ',', ' ');
        throw new \RuntimeException($origine === 'minimum'
            ? sprintf('Prix refusé pour « %s » : %s GNF par %s est sous le prix de vente minimum (%s GNF) fixé sur la fiche produit.',
                $this->libelle, $fg($prix), self::motUnite($this->unite), $fg($plancher))
            : sprintf('Vente à perte refusée pour « %s » : le prix de vente (%s GNF) est inférieur au prix de revient (%s GNF).',
                $this->libelle, $fg($prix), $fg($plancher)));
    }

    public function latestLigneCommande()
    {
        return $this->hasOne(LigneCommande::class)->latestOfMany(); // uses created_at or id
    }

        
    
    

}
