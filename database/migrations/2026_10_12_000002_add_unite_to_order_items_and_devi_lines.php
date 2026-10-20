<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unité du produit figée sur les lignes de commande e-commerce et de devis,
 * comme sur les lignes de vente : une commande ou un devis déjà émis reste
 * juste si la fiche produit change d'unité ensuite.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_items', 'devi_lines'] as $table) {
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
        foreach (['order_items', 'devi_lines'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('unite');
            });
        }
    }
};
