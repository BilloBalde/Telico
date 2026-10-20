<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Permissions et rôles liés aux emplacements de stock, partagés entre la
 * migration (bases déjà en service) et PermissionSeeder (installation neuve),
 * pour que les deux chemins aboutissent exactement au même état.
 */
class LocationPermissions
{
    /** Module => [slug => libellé]. */
    public const PERMISSIONS = [
        'Emplacements' => [
            'stores.all' => 'Accès à tous les magasins et entrepôts (sinon : uniquement son magasin)',
        ],
        'Stock' => [
            'stock.view'      => 'Voir les stocks par emplacement',
            'stock.adjust'    => 'Ajuster le stock / saisir un inventaire',
            'stock.movements' => 'Consulter le journal des mouvements et la traçabilité',
        ],
        'Commandes e-commerce' => [
            'orders.assign' => 'Attribuer une commande en ligne à une boutique',
        ],
        'Associés' => [
            'associates.manage' => 'Gérer les associés : fonds, apports, versements, relevés',
            'associates.self'   => 'Consulter son propre relevé d\'associé (lecture seule)',
        ],
        'Partenaires' => [
            // Ventes tierces : produits pris chez un partenaire et revendus.
            'partners.settle' => 'Enregistrer les règlements aux partenaires (ventes tierces)',
        ],
        'Transferts' => [
            'transfers.view'    => 'Voir les transferts',
            'transfers.create'  => 'Préparer / demander un transfert',
            'transfers.approve' => 'Autoriser ou refuser un transfert',
            'transfers.execute' => 'Expédier et réceptionner un transfert',
            'transfers.edit'    => 'Corriger un transfert terminé',
        ],
    ];

    /** Nouveaux rôles (slug => libellé). Créés par slug, jamais par id. */
    public const ROLES = [
        'magasinier'  => 'Magasinier',
        'responsable' => 'Responsable magasin',
        // Associé : accès en lecture à son seul relevé, rien d'autre.
        'associe'     => 'Associé',
    ];

    /**
     * Nouvelles permissions accordées à chaque rôle, par slug de rôle.
     * Les rôles admin et superuser reçoivent toutes les nouvelles permissions.
     */
    public const PRESETS = [
        'associe' => [
            'associates.self',
        ],
        'user' => [ // Vendeur
            'stock.view', 'transfers.view',
        ],
        'comptable' => [
            'stores.all', 'stock.view', 'partners.settle',
        ],
        'achat' => [
            'stores.all', 'stock.view', 'stock.movements', 'transfers.view', 'transfers.create',
        ],
        'magasinier' => [
            'dashboard.view', 'products.view', 'purchases.view',
            'stock.view', 'stock.adjust', 'stock.movements',
            'transfers.view', 'transfers.create', 'transfers.execute',
        ],
        'responsable' => [
            'dashboard.view', 'products.view',
            'sales.view', 'sales.create',
            'customers.view', 'customers.create',
            'payments.view', 'payments.create',
            'reports.view', 'reports.export',
            // Commandes attribuées à sa boutique : il les voit, les approuve
            // (depuis le stock de sa boutique) et les livre.
            'orders.view', 'orders.validate', 'orders.deliver',
            'stock.view', 'stock.adjust', 'stock.movements',
            'transfers.view', 'transfers.create', 'transfers.approve', 'transfers.execute',
        ],
    ];

    public static function slugs(): array
    {
        return collect(self::PERMISSIONS)->flatMap(fn ($perms) => array_keys($perms))->all();
    }

    /**
     * Idempotent. Crée les permissions ; si les rôles de base existent (base
     * déjà initialisée), crée aussi les nouveaux rôles et leurs presets.
     *
     * Les employés dont les permissions ont été personnalisées (user_permissions)
     * ne suivent plus le preset de leur rôle : on leur ajoute les nouvelles
     * permissions que leur rôle reçoit, sinon un manager personnalisé perdrait
     * par exemple l'accès aux transferts qu'il avait jusqu'ici.
     */
    public static function install(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $module => $permissions) {
            foreach ($permissions as $slug => $name) {
                DB::table('permissions')->updateOrInsert(
                    ['slug' => $slug],
                    ['name' => $name, 'module' => $module, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (! DB::table('roles')->where('slug', 'admin')->exists()) {
            return; // Base vierge : RolesSeeder + PermissionSeeder s'en chargeront.
        }

        foreach (self::ROLES as $slug => $name) {
            if (! DB::table('roles')->where('slug', $slug)->exists()) {
                DB::table('roles')->insert(['slug' => $slug, 'nameRole' => $name, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $permissionIds = DB::table('permissions')->whereIn('slug', self::slugs())->pluck('id', 'slug');
        $allPermissionIds = DB::table('permissions')->pluck('id', 'slug');
        $roleIds = DB::table('roles')->pluck('id', 'slug');

        $grants = self::PRESETS;
        $grants['admin'] = self::slugs();
        $grants['superuser'] = self::slugs();

        foreach ($grants as $roleSlug => $slugs) {
            $roleId = $roleIds[$roleSlug] ?? null;
            if (! $roleId) {
                continue;
            }

            $isNewRole = array_key_exists($roleSlug, self::ROLES);

            foreach ($slugs as $slug) {
                // Pour les nouveaux rôles, le preset complet (y compris les
                // permissions historiques comme sales.view) ; pour les rôles
                // existants, seulement les nouvelles permissions.
                $permissionId = $isNewRole ? ($allPermissionIds[$slug] ?? null) : ($permissionIds[$slug] ?? null);
                if (! $permissionId) {
                    continue;
                }

                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now, 'created_at' => $now]
                );

                if ($isNewRole) {
                    continue;
                }

                $customised = DB::table('user_permissions')
                    ->join('users', 'users.id', '=', 'user_permissions.user_id')
                    ->where('users.role_id', $roleId)
                    ->distinct()
                    ->pluck('user_permissions.user_id');

                foreach ($customised as $userId) {
                    DB::table('user_permissions')->updateOrInsert(
                        ['user_id' => $userId, 'permission_id' => $permissionId],
                        ['updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }
    }
}
