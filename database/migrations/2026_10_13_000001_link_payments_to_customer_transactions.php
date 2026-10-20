<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reçu de transaction (versement global d'un client réparti sur ses factures
 * impayées) :
 * - chaque paiement créé par le versement est rattaché à la transaction, pour
 *   que le reçu liste les factures réglées ;
 * - le solde du client APRÈS le versement est enregistré. La colonne
 *   `balance` contient le solde AVANT (elle était lue au début de
 *   l'opération), mais le reçu l'affichait comme « balance restante ».
 *   Les anciennes lignes ne sont pas modifiées : le reçu en déduit le solde
 *   après versement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('transaction_facture_id')->nullable()->after('facture_id')
                ->constrained('transaction_factures')->nullOnDelete();
        });

        Schema::table('transaction_factures', function (Blueprint $table) {
            $table->double('balance_apres')->nullable()->after('balance');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_factures', function (Blueprint $table) {
            $table->dropColumn('balance_apres');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaction_facture_id');
        });
    }
};
