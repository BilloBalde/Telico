<?php

use App\Support\LocationPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventes tierces : un produit que le vendeur n'a pas est pris chez un
 * partenaire (un ami commerçant) et revendu au client. Le prix payé au
 * partenaire est déjà sur la ligne de vente ; on enregistre ici les
 * règlements faits au partenaire, pour suivre ce qui lui reste dû.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->string('mode', 40)->default('cash');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['partner_id', 'paid_at']);
        });

        // Permission « partners.settle » (admin, superuser, comptable).
        LocationPermissions::install();
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_settlements');
    }
};
