<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unité du produit figée sur les lignes d'achat, comme sur les ventes,
 * commandes et devis : un achat déjà reçu reste juste (« 40 cartons ») si la
 * fiche produit change d'unité ensuite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('unite', 20)->nullable()->after('quantity');
        });

        DB::table('purchase_order_items')->whereNull('unite')->update([
            'unite' => DB::raw('(SELECT p.unite FROM products p WHERE p.id = purchase_order_items.product_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('unite');
        });
    }
};
