<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unité du produit figée sur les transferts et sur le journal des mouvements
 * de stock, comme sur les ventes et les achats : « 12 cartons transférés »
 * reste juste si la fiche produit change d'unité ensuite.
 *
 * Le journal est en ajout seul : on ne fait ici que renseigner une colonne
 * nouvelle (aucune quantité existante n'est modifiée).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['stock_transfers', 'stock_movements'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('unite', 20)->nullable()->after('quantity');
            });

            DB::table($table)->whereNull('unite')->update([
                'unite' => DB::raw("(SELECT p.unite FROM products p WHERE p.id = {$table}.product_id)"),
            ]);
        }
    }

    public function down(): void
    {
        foreach (['stock_transfers', 'stock_movements'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('unite');
            });
        }
    }
};
