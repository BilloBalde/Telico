<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les ventes antérieures aux unités (avant le 9 octobre 2026) n'ont pas
 * d'unité enregistrée. Les rapports regroupent désormais les quantités par
 * unité (« 12 cartons · 30 pièces ») : on y inscrit l'unité de leur produit.
 * Données seulement, aucune quantité ni aucun montant n'est modifié.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sales')->whereNull('unite')->update([
            'unite' => DB::raw("COALESCE((SELECT p.unite FROM products p WHERE p.id = sales.product_id), 'pcs')"),
        ]);
    }

    public function down(): void
    {
        // Sans effet : on ne sait plus quelles lignes étaient vides.
    }
};
