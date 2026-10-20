<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Facture;
use App\Models\Fund;
use App\Models\FundTransaction;
use App\Models\Place;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\FundService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Argent et marchandises d'un associé, séparés des fonds propres.
 * Règles : fonds choisi par ligne, bénéfice à l'encaissement, coût de revient,
 * pertes partagées, argent des ventes mis de côté, versement ventilé
 * capital / bénéfice, clé fixe 20 % patron / 80 % associé, lecture seule.
 */
class FundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $principal;
    private Store $entrepot;
    private Product $ciment;
    private Customer $client;
    private Fund $fonds;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->principal = Store::create(['store_name' => 'Magasin principal', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => true]);
        $this->entrepot = Store::create(['store_name' => 'Entrepôt 5', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'entrepot', 'parent_id' => $this->principal->id]);
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x', 'price_sale' => 45000, 'price' => 60000]);
        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620', 'address' => 'Madina']);

        $this->actingAs($this->admin)->post(route('associates.store'), [
            'name' => 'Fonds Diallo', 'associate_name' => 'Mamadou Diallo', 'owner_share' => 20, 'associate_share' => 80, 'active' => 1,
        ])->assertRedirect();
        $this->fonds = Fund::firstOrFail();
    }

    private function user(string $name, int|string $role): User
    {
        return User::create([
            'name' => $name, 'email' => str()->slug($name) . uniqid() . '@example.com', 'username' => str()->slug($name) . uniqid(),
            'password' => Hash::make('password'), 'role_id' => is_int($role) ? $role : Role::where('slug', $role)->value('id'),
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
    }

    private function apport(float $montant)
    {
        return $this->actingAs($this->admin)->post(route('associates.transactions.store', $this->fonds), [
            'kind' => 'apport', 'amount' => $montant, 'date' => now()->toDateString(),
        ]);
    }

    private function acheter(?Fund $fonds, int $qte, float $prix)
    {
        return $this->actingAs($this->admin)->post(route('purchase-orders.store'), [
            'reference' => 'PO-' . uniqid(), 'store_id' => $this->entrepot->id, 'fund_id' => $fonds?->id,
            'origin' => 'guinee', 'date_emis' => now()->toDateString(), 'date_recu' => now()->toDateString(),
            'lines' => [['product_id' => $this->ciment->id, 'quantity' => $qte, 'unit_price_foreign' => $prix]],
        ]);
    }

    private function vendre(int $qte, ?Fund $fonds, float $avance)
    {
        return $this->actingAs($this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-' . uniqid(), 'store_id' => $this->principal->id, 'customer_id' => $this->client->id,
            'avance' => $avance, 'paid_by' => 'cash',
            'sales' => [1 => array_filter(['product_id' => $this->ciment->id, 'quantity' => $qte, 'prix' => 60000,
                'store_id' => $this->entrepot->id, 'fund_id' => $fonds?->id, 'is_third_party' => '0'], fn ($v) => $v !== null)],
        ]);
    }

    private function stock(?Fund $fonds): int
    {
        return app(StockService::class)->availableForFund($this->entrepot->id, $this->ciment->id, $fonds?->id);
    }

    private function releve(): array
    {
        return app(FundService::class)->statement($this->fonds->fresh());
    }

    public function test_cycle_complet_apport_achat_vente_partage_et_versements(): void
    {
        // Apport de 10 000 000, achat de 200 sacs à 50 000 avec cet argent.
        $this->apport(10_000_000)->assertSessionHas('success');
        $this->acheter($this->fonds, 200, 50000)->assertSessionHasNoErrors();
        // Et 100 sacs aux fonds propres, dans le même entrepôt.
        $this->acheter(null, 100, 45000)->assertSessionHasNoErrors();

        $this->assertSame(200, $this->stock($this->fonds));
        $this->assertSame(100, $this->stock(null));
        // L'achat de l'associé n'a pas touché au prix de revient des fonds propres.
        $this->assertEquals(45000, (float) $this->ciment->fresh()->price_sale);

        // Plus d'argent disponible : un nouvel achat de l'associé est refusé.
        $this->acheter($this->fonds, 1, 50000)->assertSessionHasErrors('fund_id');

        // 100 sacs payés comptant, 50 sacs à crédit, 20 sacs des fonds propres.
        $this->vendre(100, $this->fonds, 6_000_000)->assertRedirect(route('factures.index'));
        $this->vendre(50, $this->fonds, 0)->assertRedirect(route('factures.index'));
        $this->vendre(20, null, 1_200_000)->assertRedirect(route('factures.index'));

        $this->assertSame(50, $this->stock($this->fonds));
        $this->assertSame(80, $this->stock(null));
        $this->assertEquals(50000, (float) Sale::where('fund_id', $this->fonds->id)->value('unit_cost'));
        // La vente des fonds propres garde le coût des fonds propres.
        $this->assertEquals(15000 * 20, (float) Sale::whereNull('fund_id')->value('interet'));

        // Bénéfice à l'encaissement : seule la vente payée compte.
        $r = $this->releve();
        $this->assertEquals(9_000_000, $r['ventes_facturees']);
        $this->assertEquals(6_000_000, $r['ventes_encaissees']);
        $this->assertEquals(1_000_000, $r['benefice_encaisse']);
        $this->assertEquals(500_000, $r['benefice_en_attente']);
        $this->assertEquals(800_000, $r['part_associe']);
        $this->assertEquals(200_000, $r['part_patron']);
        $this->assertEquals(2_500_000, $r['stock_valeur']);
        // Compte : 10 M apportés − 10 M achetés + 6 M encaissés.
        $this->assertEquals(6_000_000, $r['tresorerie']);

        // Le client paie la moitié de sa dette : la moitié du bénéfice de cette vente entre.
        $facture = Facture::where('montant_total', 3_000_000)->firstOrFail();
        $facture->update(['reste' => 1_500_000]);
        $r = $this->releve();
        $this->assertEquals(1_250_000, $r['benefice_encaisse']);

        // Versement ventilé : 5 M de capital + 800 000 de bénéfice.
        $this->actingAs($this->admin)->post(route('associates.transactions.store', $this->fonds), [
            'kind' => 'versement', 'capital' => 5_000_000, 'benefice' => 800_000, 'date' => now()->toDateString(),
        ])->assertSessionHas('success');
        $this->assertSame(2, FundTransaction::where('fund_id', $this->fonds->id)->whereIn('type', ['remboursement_capital', 'versement_benefice'])->count());

        $r = $this->releve();
        $this->assertEquals(5_000_000, $r['capital_restant']);
        $this->assertEquals(200_000, $r['benefice_a_verser']); // 80 % de 1 250 000 = 1 000 000, dont 800 000 versés

        // Pas plus de bénéfice que ce qui a été encaissé.
        $this->actingAs($this->admin)->post(route('associates.transactions.store', $this->fonds), [
            'kind' => 'versement', 'benefice' => 300_000, 'date' => now()->toDateString(),
        ])->assertSessionHasErrors('benefice');

        // Le patron prélève ses 20 %.
        $this->actingAs($this->admin)->post(route('associates.transactions.store', $this->fonds), [
            'kind' => 'prelevement', 'amount' => 250_000, 'date' => now()->toDateString(),
        ])->assertSessionHas('success');
        $this->assertEquals(0, $this->releve()['part_patron_restante']);
    }

    public function test_perte_sur_le_stock_de_l_associe_partagee(): void
    {
        $this->apport(1_000_000);
        $this->acheter($this->fonds, 20, 50000)->assertSessionHasNoErrors();

        // Inventaire : 2 sacs perdus sur le stock de l'associé.
        $this->actingAs($this->admin)->post(route('stock.adjust'), [
            'store_id' => $this->entrepot->id, 'product_id' => $this->ciment->id, 'counted' => 18,
            'type' => 'INVENTORY', 'reason' => 'Sacs déchirés', 'fund_id' => $this->fonds->id,
        ])->assertSessionHas('success');

        $this->assertSame(18, $this->stock($this->fonds));
        $r = $this->releve();
        $this->assertEquals(-100_000, $r['ecarts_stock']);
        $this->assertEquals(-80_000, $r['part_associe']);
        $this->assertEquals(-20_000, $r['part_patron']);
    }

    public function test_le_vendeur_ne_peut_vendre_que_le_stock_du_fonds_choisi(): void
    {
        $this->apport(1_000_000);
        $this->acheter($this->fonds, 10, 50000);
        $this->acheter(null, 5, 45000);

        // 8 sacs « fonds propres » : il n'y en a que 5, même si l'associé en a 10 à côté.
        $this->vendre(8, null, 0)->assertSessionHas('error', fn ($m) => str_contains($m, 'Disponible : 5 pièces. Demandé : 8 pièces.'));
        $this->assertSame(10, $this->stock($this->fonds));

        // Le transfert garde le propriétaire.
        $this->actingAs($this->admin)->post(route('transfers.store'), [
            'product_id' => $this->ciment->id, 'from_store_id' => $this->entrepot->id, 'to_store_id' => $this->principal->id,
            'quantity' => 4, 'fund_id' => $this->fonds->id, 'execute_now' => 1,
        ])->assertSessionHas('success');
        $this->assertSame(6, $this->stock($this->fonds));
        $this->assertSame(4, app(StockService::class)->availableForFund($this->principal->id, $this->ciment->id, $this->fonds->id));
        $this->assertSame(5, $this->stock(null));
        $this->assertTrue(StockMovement::where('type', 'TRANSFER_IN')->where('fund_id', $this->fonds->id)->exists());
    }

    public function test_facturation_et_devis_avec_les_marchandises_de_l_associe(): void
    {
        $this->apport(1_000_000);
        $this->acheter($this->fonds, 20, 50000)->assertSessionHasNoErrors();
        $this->acheter(null, 10, 45000)->assertSessionHasNoErrors();

        // Facturation : une ligne de l'associé (5 sacs), une des fonds propres (3 sacs).
        $this->actingAs($this->admin)->post(route('facturations.store'), [
            'numeroFacture' => 'FAC-1', 'avance' => 0, 'customer_id' => $this->client->id, 'store_id' => $this->entrepot->id,
            'products' => [
                1 => ['productName' => 'Ciment', 'cartons' => 5, 'qty_per_ctn' => 1, 'price' => 60000, 'subtotal' => 300000, 'fund_id' => $this->fonds->id],
                2 => ['productName' => 'Ciment', 'cartons' => 3, 'qty_per_ctn' => 1, 'price' => 60000, 'subtotal' => 180000],
            ],
        ])->assertSessionHas('success');

        $this->assertSame(15, $this->stock($this->fonds));
        $this->assertSame(7, $this->stock(null));
        $this->assertEquals(50000, (float) Sale::where('numeroFacture', 'FAC-1')->where('fund_id', $this->fonds->id)->value('unit_cost'));
        $this->assertEquals(10000 * 5, (float) Sale::where('numeroFacture', 'FAC-1')->where('fund_id', $this->fonds->id)->value('interet'));
        $this->assertEquals(15000 * 3, (float) Sale::where('numeroFacture', 'FAC-1')->whereNull('fund_id')->value('interet'));

        // Devis : 4 sacs de l'associé, validé puis payé.
        $devis = \App\Models\Devi::create(['store_id' => $this->entrepot->id, 'customer_id' => $this->client->id, 'total_amount' => 240000,
            'status' => 'accepted', 'created_by' => $this->admin->id]);
        \App\Models\DeviLine::create(['devis_id' => $devis->id, 'product_id' => $this->ciment->id, 'fund_id' => $this->fonds->id,
            'quantity' => 4, 'unit_price' => 60000, 'total_price' => 240000]);
        $this->actingAs($this->admin)->post(route('devis.validate', $devis->id))->assertRedirect();

        $this->assertSame(11, $this->stock($this->fonds));
        $factureDevis = Facture::where('channel', Facture::CHANNEL_DEVIS)->firstOrFail();
        $factureDevis->update(['reste' => 0]);

        // Seul le devis est payé : 4 sacs × 10 000 de bénéfice encaissé.
        $r = $this->releve();
        $this->assertEquals(40_000, $r['benefice_encaisse']);
        $this->assertEquals(50_000, $r['benefice_en_attente']);
        $this->assertEquals(32_000, $r['part_associe']);
    }

    public function test_l_associe_ne_voit_que_son_releve_en_lecture(): void
    {
        $associe = $this->user('Mamadou', 'associe');
        $this->actingAs($this->admin)->put(route('associates.update', $this->fonds), [
            'name' => 'Fonds Diallo', 'associate_name' => 'Mamadou Diallo', 'owner_share' => 20, 'associate_share' => 80,
            'active' => 1, 'user_id' => $associe->id,
        ])->assertRedirect();
        $autre = Fund::create(['name' => 'Fonds Bah', 'associate_name' => 'Bah']);

        $this->actingAs($associe->fresh())->get(route('associates.me'))->assertRedirect(route('associates.show', $this->fonds->id));
        $this->actingAs($associe->fresh())->get(route('associates.show', $this->fonds))->assertOk()->assertSee('Relevé')->assertDontSee('Enregistrer l\'apport');
        $this->actingAs($associe->fresh())->get(route('associates.show', $autre))->assertForbidden();

        // Rien d'autre du back-office, ni écriture.
        $this->actingAs($associe->fresh())->get(route('home'))->assertRedirect(route('associates.me'));
        $this->actingAs($associe->fresh())->get(route('stock.index'))->assertRedirect(route('associates.me'));
        $this->actingAs($associe->fresh())->post(route('associates.transactions.store', $this->fonds), ['kind' => 'apport', 'amount' => 1, 'date' => now()->toDateString()])
            ->assertRedirect(route('associates.me'));
        $this->assertSame(0, FundTransaction::count());
    }

    public function test_la_cle_de_partage_est_figee_une_fois_l_activite_commencee(): void
    {
        $this->apport(100);
        $this->actingAs($this->admin)->put(route('associates.update', $this->fonds), [
            'name' => 'Fonds Diallo', 'associate_name' => 'Mamadou Diallo', 'owner_share' => 50, 'associate_share' => 50, 'active' => 1,
        ])->assertRedirect();

        $this->assertEquals(20, $this->fonds->fresh()->owner_share);
        $this->assertEquals(80, $this->fonds->fresh()->associate_share);
    }
}
