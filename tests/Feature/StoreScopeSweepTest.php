<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DebtReminder;
use App\Models\Devi;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Facture;
use App\Models\Place;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Balayage du périmètre magasin : un employé rattaché à un magasin ne voit ni
 * ne modifie les documents des autres magasins, sur toutes les pages (bons,
 * devis, dépenses, rappels, clients, anciennes pages hors menu). La vue
 * globale voit tout. Rapport journalier : une dépense compte une seule fois.
 */
class StoreScopeSweepTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendeur;
    private Store $madina;
    private Store $kaloum;
    private Customer $client;
    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        // Les deux magasins appartiennent au patron (stores.user_id).
        $this->madina = $this->magasin('Madina');
        $this->kaloum = $this->magasin('Kaloum');
        $this->vendeur = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->madina->id);
        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620000000', 'address' => 'Madina']);
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x', 'price_sale' => 45000, 'price' => 60000]);
        foreach ([$this->madina, $this->kaloum] as $m) {
            StoreProduct::create(['store_id' => $m->id, 'product_id' => $this->ciment->id, 'quantity' => 100]);
        }
    }

    private function magasin(string $nom): Store
    {
        return Store::create(['store_name' => $nom, 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin']);
    }

    private function user(string $name, int $role, ?int $storeId = null): User
    {
        $user = User::create([
            'name' => $name, 'email' => str()->slug($name) . uniqid() . '@example.com', 'username' => str()->slug($name) . uniqid(),
            'password' => Hash::make('password'), 'role_id' => $role, 'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
        if ($storeId) {
            $user->assignToStore($storeId);
        }

        return $user;
    }

    private function vente(User $par, Store $magasin, int $avance = 0): Facture
    {
        $this->actingAs($par)->post(route('sales.store'), [
            'numeroFacture' => 'F-' . uniqid(), 'store_id' => $magasin->id, 'customer_id' => $this->client->id,
            'avance' => $avance, 'paid_by' => 'cash',
            'sales' => [1 => ['product_id' => $this->ciment->id, 'quantity' => 1, 'prix' => 60000, 'store_id' => $magasin->id]],
        ])->assertSessionHas('success');

        return Facture::latest('id')->firstOrFail();
    }

    public function test_factures_et_bons_d_un_autre_magasin_refuses(): void
    {
        $kaloum = $this->vente($this->admin, $this->kaloum);
        $madina = $this->vente($this->vendeur, $this->madina);

        foreach (['factures.show', 'factures.bon-de-commande', 'factures.bon-de-sortie'] as $route) {
            $this->actingAs($this->vendeur)->get(route($route, $kaloum->numero_facture))->assertForbidden();
            $this->actingAs($this->vendeur)->get(route($route, $madina->numero_facture))->assertOk();
            $this->actingAs($this->admin)->get(route($route, $kaloum->numero_facture))->assertOk();
        }
        $this->actingAs($this->vendeur)->get(route('factures.edit', $kaloum->id))->assertForbidden();
        $this->actingAs($this->vendeur)->put(route('factures.update', $kaloum->id))->assertForbidden();
        $this->assertSame('non livré', $kaloum->fresh()->livraison);

    }

    public function test_quitter_une_vente_ne_supprime_plus_n_importe_quelle_facture(): void
    {
        $kaloum = $this->vente($this->admin, $this->kaloum);
        $madina = $this->vente($this->vendeur, $this->madina);

        $this->actingAs($this->vendeur)->post(route('exitSale', $kaloum->numero_facture))->assertForbidden();
        $this->actingAs($this->vendeur)->post(route('exitSale', $madina->numero_facture))->assertSessionHas('error');
        $this->assertNotNull($kaloum->fresh());
        $this->assertNotNull($madina->fresh());

        // Facture vide (ancien flux) de son magasin : supprimable.
        Facture::create(['numero_facture' => 'VIDE-1', 'store_id' => $this->madina->id, 'customer_id' => $this->client->id, 'montant_total' => 0, 'avance' => 0, 'reste' => 0]);
        $this->actingAs($this->vendeur)->post(route('exitSale', 'VIDE-1'))->assertRedirect(route('sales.index'));
        $this->assertNull(Facture::where('numero_facture', 'VIDE-1')->first());
    }

    public function test_devis_d_un_autre_magasin_refuse(): void
    {
        $devis = Devi::create(['numero_devis' => 'DEV-K', 'store_id' => $this->kaloum->id, 'customer_id' => $this->client->id, 'total_amount' => 0, 'status' => 'draft', 'created_by' => $this->admin->id]);

        foreach (['devis.show', 'devis.edit', 'devis.pdf', 'devis.pdf-download'] as $route) {
            $this->actingAs($this->vendeur)->get(route($route, $devis->id))->assertForbidden();
        }
        $this->actingAs($this->vendeur)->delete(route('devis.destroy', $devis->id))->assertForbidden();
        $this->assertNotNull($devis->fresh());
        $this->actingAs($this->admin)->get(route('devis.show', $devis->id))->assertOk();
    }

    public function test_depenses_rappels_et_clients_limites_au_magasin(): void
    {
        $categorie = ExpenseCategory::forceCreate(['slug' => 'transport', 'categoryName' => 'Transport']);
        $depense = fn (Store $m, int $montant) => Expense::forceCreate(['reference' => 'D-' . uniqid(), 'expense_categories_id' => $categorie->id,
            'store_id' => $m->id, 'user_id' => $this->admin->id, 'amount' => $montant, 'exp_mode' => 'others', 'description' => 'x']);
        $dMadina = $depense($this->madina, 1000);
        $dKaloum = $depense($this->kaloum, 5000);

        $liste = $this->actingAs($this->vendeur)->get(route('expenses.index'))->assertOk();
        $this->assertSame([$dMadina->id], $liste->viewData('expenses')->pluck('id')->all());
        $this->actingAs($this->vendeur)->get(route('expenses.edit', $dKaloum))->assertForbidden();
        $this->assertCount(2, $this->actingAs($this->admin)->get(route('expenses.index'))->viewData('expenses'));

        // Rappels de dettes : seulement ceux des factures de son magasin.
        $fKaloum = $this->vente($this->admin, $this->kaloum);
        $fMadina = $this->vente($this->vendeur, $this->madina);
        DebtReminder::create(['customer_id' => $this->client->id, 'facture_id' => $fKaloum->id, 'amount' => 60000, 'status' => 'sent']);
        DebtReminder::create(['customer_id' => $this->client->id, 'facture_id' => $fMadina->id, 'amount' => 60000, 'status' => 'sent']);
        $rappels = $this->actingAs($this->vendeur)->get(route('admin.debt-reminders.index'))->assertOk()->viewData('reminders');
        $this->assertEquals(60000, $rappels->first()->total);
        $this->assertEquals(120000, $this->actingAs($this->admin)->get(route('admin.debt-reminders.index'))->viewData('reminders')->first()->total);
        // « Résolu » par le vendeur : seulement son rappel.
        $this->actingAs($this->vendeur)->post(route('admin.debt-reminders.resolve', $this->client->id));
        $this->assertSame('sent', DebtReminder::where('facture_id', $fKaloum->id)->value('status'));

        // Liste des clients : solde de son périmètre seulement (60 000, pas 120 000).
        $this->actingAs($this->vendeur)->get(route('customers.index'))->assertOk()->assertSee('60000 FG')->assertDontSee('120000');
    }

    public function test_anciennes_pages_hors_menu_reservees(): void
    {
        foreach (['/mouvements', '/journalier', '/journaliers/paiements', '/logistics', '/purchases/export-excel', '/purchases/export-pdf'] as $url) {
            $this->actingAs($this->vendeur)->get($url)->assertForbidden();
        }
        $this->actingAs($this->admin)->get('/journalier')->assertOk();
    }

    public function test_rapport_journalier_une_depense_comptee_une_seule_fois(): void
    {
        // Le patron possède les deux magasins : l'ancien calcul (via stores.user_id)
        // comptait sa dépense une fois par magasin.
        $categorie = ExpenseCategory::forceCreate(['slug' => 'divers', 'categoryName' => 'Divers']);
        Expense::forceCreate(['reference' => 'D-1', 'expense_categories_id' => $categorie->id, 'store_id' => $this->kaloum->id,
            'user_id' => $this->admin->id, 'amount' => 1000, 'exp_mode' => 'others', 'description' => 'x']);

        $global = $this->actingAs($this->admin)->get(route('reports.daily', ['period' => 'daily']))->assertOk();
        $this->assertEquals(1000, $global->viewData('totalDepenses'));

        // Le vendeur de Madina ne voit pas la dépense de Kaloum.
        $vendeur = $this->actingAs($this->vendeur)->get(route('reports.daily', ['period' => 'daily']))->assertOk();
        $this->assertEquals(0, $vendeur->viewData('totalDepenses'));
    }
}
