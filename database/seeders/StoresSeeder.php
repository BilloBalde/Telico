<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoresSeeder extends Seeder
{
    public function run()
    {
        $placeId = DB::table('places')->where('placeName', 'Conakry')->value('id')
            ?? DB::table('places')->value('id');
        $userId = DB::table('users')->where('email', 'info@ibratechengineer.com')->value('id')
            ?? DB::table('users')->value('id');

        if (!$placeId || !$userId) {
            return;
        }

        $stores = [
            [
                'store_name' => 'Quincaillerie Madina',
                'place_id' => $placeId,
                'user_id' => $userId,
                // Vide plutôt qu'un nom de fichier fictif : les vues testent la
                // valeur et affichent leur visuel de repli, sans requête 404.
                'store_picture' => '',
                'status' => 1,
                'description' => 'Boutique principale : peinture, outillage, serrurerie, plomberie, électricité et divers.',
                'address' => 'Madina, Ecole Gare Routière Dabola Camion, Conakry',
                'phone' => '625 45 35 45',
            ],
            [
                'store_name' => 'Dépôt Madina',
                'place_id' => $placeId,
                'user_id' => $userId,
                // Vide plutôt qu'un nom de fichier fictif : les vues testent la
                // valeur et affichent leur visuel de repli, sans requête 404.
                'store_picture' => '',
                'status' => 1,
                'description' => 'Entrepôt secondaire pour le réapprovisionnement.',
                'address' => 'Madina, Conakry',
                'phone' => '628 59 31 10',
            ],
        ];

        foreach ($stores as $store) {
            DB::table('stores')->updateOrInsert(
                ['store_name' => $store['store_name']],
                $store
            );
        }

        // Hiérarchie des emplacements : la boutique est le magasin principal,
        // le dépôt est un entrepôt qui lui est rattaché (sinon une installation
        // neuve n'avait ni magasin principal ni entrepôt).
        $boutiqueId = DB::table('stores')->where('store_name', 'Quincaillerie Madina')->value('id');
        DB::table('stores')->where('id', $boutiqueId)->update(['type' => 'magasin', 'is_main' => true, 'parent_id' => null]);
        DB::table('stores')->where('store_name', 'Dépôt Madina')->update(['type' => 'entrepot', 'is_main' => false, 'parent_id' => $boutiqueId]);

        // Vendeur de démonstration affecté à la boutique : sans affectation, il
        // ne voyait aucun stock à la caisse et son tableau de bord le renvoyait
        // à la connexion.
        $vendeur = \App\Models\User::where('email', 'storeuser@example.com')->first();
        if ($vendeur && $boutiqueId && ! $vendeur->store_id) {
            $vendeur->assignToStore((int) $boutiqueId);
        }
    }
}
