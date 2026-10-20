<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Devi;
use App\Models\DeviLine;
use App\Models\Facture;
use App\Models\Partner;
use App\Models\Place;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Prix de vente minimum (fiche produit) : aucune vente sous le plus haut de ce
 * minimum et du prix de revient — caisse, facturation manuelle, devis — contrôlé
 * par le serveur. Ventes tierces : le plancher reste le prix payé au partenaire.
 */
class PrixMinimumTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $magasin;
    private Customer $client;
    private Category $categorie;
    private Product $robinet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Patron', 'email' => 'patron@example.com', 'username' => 'patron',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->magasin = Store::create(['store_name' => 'Madina', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => true]);
        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620', 'address' => 'Madina']);
        $this->categorie = Category::create(['slug' => 'plomberie', 'category_type' => 'Plomberie', 'description' => 'x']);

        // Revient 10 000, prix de vente 13 000, minimum 12 000 (par carton).
        $this->robinet = Product::create(['libelle' => 'Robinet', 'sku' => 'ROB', 'description' => 'x', 'unite' => 'carton', 'pcs_par_carton' => 6,
            'price_sale' => 10000, 'price' => 13000, 'price_sale_minimum' => 12000]);
        StoreProduct::create(['store_id' => $this->magasin->id, 'product_id' => $this->robinet->id, 'quantity' => 100]);
    }

    private function creerProduit(array $donnees): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post(route('produits.store'), $donnees + [
            'categories' => [$this->categorie->id], 'libelle' => 'Vanne', 'sku' => 'VAN-' . uniqid(), 'description' => 'x',
            'price_sale' => 10000, 'stock_initial' => 10, 'store_id' => $this->magasin->id, 'unite' => 'pcs',
        ]);
    }

    private function vendre(float $prix, array $ligne = [])
    {
        return $this->actingAs($this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-' . uniqid(), 'store_id' => $this->magasin->id, 'customer_id' => $this->client->id,
            'avance' => 0, 'paid_by' => 'cash',
            'sales' => [1 => $ligne + ['product_id' => $this->robinet->id, 'quantity' => 2, 'prix' => $prix, 'store_id' => $this->magasin->id, 'is_third_party' => '0']],
        ]);
    }

    public function test_fiche_produit_minimum_facultatif_entre_revient_et_prix_de_vente(): void
    {
        // Prix de vente calculé : 10 000 × 1,3 = 13 000.
        $this->creerProduit(['price_sale_minimum' => 10000])->assertSessionHasErrors('price_sale_minimum');
        $this->creerProduit(['price_sale_minimum' => 14000])->assertSessionHasErrors('price_sale_minimum');
        $this->creerProduit(['price_sale_minimum' => 11500])->assertSessionHasNoErrors();
        $this->assertEquals(11500, (float) Product::where('libelle', 'Vanne')->latest('id')->value('price_sale_minimum'));
        $this->creerProduit([])->assertSessionHasNoErrors();
        $this->assertNull(Product::where('libelle', 'Vanne')->latest('id')->value('price_sale_minimum'));

        // Modification : minimum au-dessus du prix de vente saisi refusé.
        $this->actingAs($this->admin)->put(route('produits.update', $this->robinet->id), [
            'libelle' => 'Robinet', 'sku' => 'ROB', 'categories' => [$this->categorie->id], 'description' => 'x',
            'price' => 13000, 'price_sale' => 10000, 'price_sale_minimum' => 13500, 'stock_restant' => 100,
            'store_id' => $this->magasin->id, 'unite' => 'carton', 'pcs_par_carton' => 6,
        ])->assertSessionHasErrors('price_sale_minimum');

        // Formulaires : champ présent, libellé dans l'unité du produit.
        $this->actingAs($this->admin)->get(route('produits.create'))->assertOk()->assertSee('Prix de vente minimum par');
        $this->actingAs($this->admin)->get(route('produits.edit', $this->robinet->id))->assertOk()->assertSee('value="12000"', false);
    }

    public function test_caisse_refuse_sous_le_minimum_et_sous_le_revient(): void
    {
        // Sous le minimum (12 000) mais au-dessus du revient : refusé par le serveur.
        $this->vendre(11000)->assertSessionHas('error', fn ($m) => str_contains($m, 'sous le prix de vente minimum (12 000 GNF)') && str_contains($m, 'par carton'));
        $this->assertSame(0, Sale::count());
        $this->assertSame(100, (int) StoreProduct::where('product_id', $this->robinet->id)->value('quantity'));

        // Au minimum : accepté (remise sur le prix de 13 000).
        $this->vendre(12000)->assertSessionHas('success');

        // Produit sans minimum : le plancher reste le prix de revient.
        $this->robinet->update(['price_sale_minimum' => null]);
        $this->vendre(9000)->assertSessionHas('error', fn ($m) => str_contains($m, 'Vente à perte'));
        $this->vendre(10500)->assertSessionHas('success');

        // Carte de la caisse : minimum transmis au script.
        $this->robinet->update(['price_sale_minimum' => 12000]);
        $this->actingAs($this->admin)->get(route('pos'))->assertOk()->assertSee('data-min-price="12000', false);
    }

    public function test_vente_tierce_plancher_du_partenaire(): void
    {
        // Produit pris chez un partenaire : le minimum de la fiche ne s'applique pas,
        // seul le prix payé au partenaire compte.
        $ami = Partner::create(['name' => 'Moussa']);
        $this->vendre(9000, ['is_third_party' => '1', 'partner_id' => $ami->id, 'purchase_price_from_partner' => 8000])->assertSessionHas('success');
        $this->vendre(7000, ['is_third_party' => '1', 'partner_id' => $ami->id, 'purchase_price_from_partner' => 8000])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Vente à perte'));
    }

    public function test_facturation_manuelle_refuse_sous_le_minimum(): void
    {
        $facturer = fn (float $prix) => $this->actingAs($this->admin)->post(route('facturations.store'), [
            'numeroFacture' => 'FAC-' . uniqid(), 'avance' => 0, 'customer_id' => $this->client->id, 'store_id' => $this->magasin->id,
            'products' => [1 => ['productName' => 'Robinet', 'cartons' => 1, 'qty_per_ctn' => 1, 'price' => $prix, 'subtotal' => $prix]],
        ]);

        $facturer(11000)->assertSessionHas('error', fn ($m) => str_contains($m, 'prix de vente minimum'));
        $this->assertSame(0, Facture::count());
        $facturer(12500)->assertSessionHas('success');

        // Le formulaire reçoit le plancher pour le signaler à la saisie.
        $this->actingAs($this->admin)->getJson('/fetch-product-details?exact=1&productName=Robinet')
            ->assertJson(['product' => ['plancher' => 12000, 'plancher_origine' => 'prix de vente minimum']]);
    }

    public function test_devis_refuse_a_la_saisie_et_a_la_validation(): void
    {
        $ligne = fn (float $prix) => ['product_id' => $this->robinet->id, 'quantity' => 1, 'unit_price' => $prix];

        $this->actingAs($this->admin)->post(route('devis.store'), [
            'store_id' => $this->magasin->id, 'customer_id' => $this->client->id, 'products' => [$ligne(11000)],
        ])->assertSessionHas('error', fn ($m) => str_contains($m, 'prix de vente minimum'));
        $this->assertSame(0, Devi::count());

        // Devis accepté à 12 500, puis le gérant relève le minimum à 12 800 :
        // la validation refuse de transformer le devis en vente.
        $devis = Devi::create(['store_id' => $this->magasin->id, 'customer_id' => $this->client->id, 'total_amount' => 12500, 'status' => 'accepted', 'created_by' => $this->admin->id]);
        DeviLine::create(['devis_id' => $devis->id, 'product_id' => $this->robinet->id, 'quantity' => 1, 'unit_price' => 12500, 'total_price' => 12500]);
        $this->robinet->update(['price_sale_minimum' => 12800]);

        $this->actingAs($this->admin)->post(route('devis.validate', $devis->id))->assertSessionHas('error', fn ($m) => str_contains($m, '12 800'));
        $this->assertSame(0, Sale::count());
        $this->assertSame(100, (int) StoreProduct::where('product_id', $this->robinet->id)->value('quantity'));

        // Formulaire de devis : plancher transmis pour le signalement à la saisie.
        $this->actingAs($this->admin)->get(route('devis.create'))->assertOk()->assertSee('"plancher":12800', false);
    }
}
