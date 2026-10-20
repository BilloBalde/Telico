<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Un employé rattaché à un magasin (vendeur, magasinier, responsable — tout
     * profil sans la permission « stores.all ») ne doit pouvoir écrire que pour
     * son propre magasin : store_id ne doit jamais venir du formulaire pour lui,
     * car c'est un champ falsifiable côté client. Retourne null pour les profils
     * à vue globale (admin, manager, comptable, achat...), qui restent libres de
     * choisir une boutique.
     */
    protected function ownStoreIdOverride(): ?int
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        return $user->forcedStoreId();
    }

    /**
     * 403 si un document (facture, devis, paiement…) appartient à un magasin
     * hors du périmètre de l'utilisateur. Contrairement à authorizeStore(), une
     * vue globale passe toujours, même pour un ancien document sans magasin.
     */
    protected function autoriserPerimetre($storeId, string $message = 'Ce document appartient à un autre magasin.'): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User && (! $user->isStoreScoped() || $user->canAccessStore($storeId)),
            403,
            $message
        );
    }

    /** 403 si l'emplacement est hors du périmètre de l'utilisateur connecté. */
    protected function authorizeStore($storeId): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canAccessStore($storeId)) {
            abort(403, 'Cet emplacement ne fait pas partie de votre périmètre.');
        }
    }
}
