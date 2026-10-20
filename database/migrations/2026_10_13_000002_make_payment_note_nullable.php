<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La note d'un paiement est facultative dans les formulaires (versement global
 * d'un client, paiement d'une facture) mais la colonne était obligatoire et
 * limitée à 191 caractères : sans note, MySQL (mode strict) refusait le
 * paiement et le vendeur voyait « Une erreur est survenue ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('note', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('note')->nullable(false)->change();
        });
    }
};
