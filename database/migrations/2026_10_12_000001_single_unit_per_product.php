<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une seule unité par produit (pièce, douzaine, carton, paquet ou rouleau),
 * choisie à la création : le stock, les achats, les ventes et les factures
 * du produit sont tous comptés dans cette unité, sans conversion.
 *
 * Remplace les unités multiples avec conversion à la vente
 * (2026_10_09_000001) : la liste `unites_vente` et les colonnes de
 * conversion des ventes disparaissent. `sales.unite` reste, comme trace de
 * l'unité au moment de la vente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unite', 20)->default('pcs')->after('pcs');
        });

        // Avec l'ancien système, le stock était compté en pièces, sauf pour un
        // produit vendu uniquement au rouleau : c'est donc son unité actuelle.
        DB::table('products')->whereNotNull('unites_vente')->orderBy('id')->each(function ($p) {
            $codes = json_decode($p->unites_vente, true) ?: [];
            DB::table('products')->where('id', $p->id)->update(['unite' => $codes === ['rouleau'] ? 'rouleau' : 'pcs']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unites_vente');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['quantite_unite', 'facteur_unite', 'prix_unite']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('quantite_unite')->nullable()->after('unite');
            $table->unsignedInteger('facteur_unite')->nullable()->after('quantite_unite');
            $table->double('prix_unite')->nullable()->after('facteur_unite');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('unites_vente')->nullable()->after('pcs');
        });
        DB::table('products')->where('unite', '!=', 'pcs')->update(['unites_vente' => DB::raw("JSON_ARRAY(unite)")]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unite');
        });
    }
};
