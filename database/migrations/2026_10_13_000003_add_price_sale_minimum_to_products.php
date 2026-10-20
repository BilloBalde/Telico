<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prix de vente minimum par produit, fixé par le gérant sur la fiche (dans
 * l'unité du produit). Facultatif : aucune vente ne peut se faire sous le plus
 * haut de ce minimum et du prix de revient — caisse, facturation, devis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_sale_minimum', 14, 2)->nullable()->after('price_sale');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price_sale_minimum');
        });
    }
};
