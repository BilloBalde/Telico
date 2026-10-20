<?php

namespace App\Models\Concerns;

use App\Models\Product;

/**
 * Ligne de document portant un produit et une quantité (vente, commande
 * e-commerce, devis) : l'unité du produit (carton, douzaine…) y est figée à
 * la création, quel que soit le circuit, pour que le document reste juste si
 * la fiche produit change d'unité ensuite.
 */
trait FigeUniteProduit
{
    protected static function bootFigeUniteProduit(): void
    {
        static::creating(function ($ligne) {
            if (! $ligne->unite && $ligne->product_id) {
                $ligne->unite = Product::whereKey($ligne->product_id)->value('unite') ?: 'pcs';
            }
        });
    }

    /** Quantité avec son unité : « 3 cartons », « 5 pièces ». */
    public function getQuantiteAfficheeAttribute(): string
    {
        return Product::quantiteEnUnite($this->quantity, $this->unite ?: $this->product?->unite);
    }

    /**
     * Conditionnement de la ligne pour les bons : « Carton (24 pcs) »,
     * « Rouleau (100 m) ». Si la fiche a changé d'unité depuis, seul le nom
     * de l'unité figée est affiché.
     */
    public function getUniteDetailAttribute(): string
    {
        $unite = $this->unite ?: $this->product?->unite;

        return $this->product && $this->product->unite === $unite
            ? $this->product->unite_libelle
            : Product::UNITES[$unite ?? 'pcs']['libelle'] ?? 'Pièce';
    }

    /** Unité au singulier, pour « 8 500 GNF / carton ». */
    public function getMotUniteAttribute(): string
    {
        return Product::motUnite($this->unite ?: $this->product?->unite);
    }
}
