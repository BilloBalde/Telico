<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Facture;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\Place;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\StoreAssignment;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Magasins, entrepôts et stocks multiples ; ventes directes depuis un entrepôt,
 * ventes multi-emplacements, transferts, achats, affectation des vendeurs,
 * restrictions par magasin, ventes flash, rapports et traçabilité.
 * Numérotation des tests = scénarios 1 à 16 du cahier des charges.
 */
class MultiLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Place $lieu;
    private Customer $client;
    private Product $ciment;
    private Product $carton;
    private Product $fer;
    private Store $principal;
    private Store $madina;
    private Store $magasinB;
    private Store $entrepot5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        Mail::fake();

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        $this->lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620000000', 'address' => 'Madina']);

        $this->principal = $this->store('TELICO — Magasin principal', ['is_main' => true]);
        $this->madina = $this->store('TELICO — Magasin Madina');
        $this->magasinB = $this->store('TELICO — Magasin B');
        $this->entrepot5 = $this->store('Entrepôt 5', ['type' => Store::TYPE_ENTREPOT, 'parent_id' => $this->principal->id]);

        $this->ciment = $this->product('Ciment 50kg', 'CIM');
        $this->carton = $this->product('Carton carreaux', 'CAR');
        $this->fer = $this->product('Fer 12', 'FER');
    }

    // ------------------------------------------------------------------
    // Outils
    // ------------------------------------------------------------------

    private function user(string $name, int|string $role, ?int $storeId = null): User
    {
        $roleId = is_int($role) ? $role : Role::where('slug', $role)->value('id');

        $user = User::create([
            'name' => $name, 'email' => str()->slug($name) . uniqid() . '@example.com', 'username' => str()->slug($name) . uniqid(),
            'password' => Hash::make('password'), 'role_id' => $roleId, 'phone' => '620000001', 'status' => 'Active', 'token' => '',
        ]);

        if ($storeId) {
            $user->assignToStore($storeId);
        }

        return $user->fresh();
    }

    private function store(string $name, array $extra = []): Store
    {
        return Store::create($extra + [
            'store_name' => $name, 'place_id' => $this->lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => $name, 'status' => 1, 'type' => Store::TYPE_MAGASIN,
        ]);
    }

    private function product(string $libelle, string $sku): Product
    {
        return Product::create(['libelle' => $libelle, 'sku' => $sku, 'description' => $libelle, 'price_sale' => 1000, 'price' => 1500]);
    }

    private function stock(Store $store, Product $product): int
    {
        return app(StockService::class)->availableInStore($store->id, $product->id);
    }

    private function approvisionner(Store $store, Product $product, int $qty): void
    {
        app(StockService::class)->increment($store->id, $product->id, $qty, ['type' => StockMovement::INITIAL]);
    }

    /** Vente au point de vente ; $lignes = [[produit, qté, emplacement|null], …]. */
    private function vendre(User $vendeur, Store $magasin, array $lignes)
    {
        $sales = [];
        foreach ($lignes as $i => [$produit, $qte, $origine]) {
            $sales[$i + 1] = array_filter([
                'product_id' => $produit->id, 'quantity' => $qte, 'prix' => 1500,
                'store_id' => $origine?->id, 'is_third_party' => '0',
            ], fn ($v) => $v !== null);
        }

        return $this->actingAs($vendeur)->post(route('sales.store'), [
            'numeroFacture' => 'F-' . uniqid(), 'store_id' => $magasin->id, 'customer_id' => $this->client->id,
            'avance' => 0, 'paid_by' => 'cash', 'sales' => $sales,
        ]);
    }

    // ------------------------------------------------------------------
    // 1 à 3 : structure
    // ------------------------------------------------------------------

    public function test_01_plusieurs_magasins_ont_des_stocks_independants(): void
    {
        $this->approvisionner($this->principal, $this->ciment, 500);
        $this->approvisionner($this->madina, $this->ciment, 300);

        $this->assertSame(500, $this->stock($this->principal, $this->ciment));
        $this->assertSame(300, $this->stock($this->madina, $this->ciment));
        $this->assertSame(0, $this->stock($this->magasinB, $this->ciment));
        $this->assertSame(800, app(StockService::class)->totalAvailable($this->ciment->id));
    }

    public function test_02_quinze_entrepots_sans_modification_du_code(): void
    {
        for ($n = 1; $n <= 15; $n++) {
            $this->actingAs($this->admin)->post(route('boutiques.store'), [
                'store_name' => "Entrepôt test $n", 'place_id' => $this->lieu->id, 'user_id' => $this->admin->id,
                'status' => 1, 'description' => 'Stockage', 'type' => 'entrepot', 'parent_id' => $this->principal->id,
            ])->assertRedirect(route('boutiques.index'));
        }

        $entrepot15 = Store::where('store_name', 'Entrepôt test 15')->firstOrFail();
        $this->assertSame(Store::TYPE_ENTREPOT, $entrepot15->type);
        $this->assertSame(16, Store::ofType(Store::TYPE_ENTREPOT)->count());

        $this->approvisionner($entrepot15, $this->ciment, 50);
        $this->actingAs($this->admin)->get(route('stock.index'))->assertOk()->assertSee('Entrepôt test 15');
        $this->assertSame(50, $this->stock($entrepot15, $this->ciment));
    }

    public function test_03_plusieurs_stocks_par_magasin_independants_et_accessibles_au_vendeur(): void
    {
        $a1 = $this->store('Stock A1', ['type' => Store::TYPE_STOCK, 'parent_id' => $this->madina->id]);
        $a2 = $this->store('Stock A2', ['type' => Store::TYPE_STOCK, 'parent_id' => $this->madina->id]);
        $this->approvisionner($a1, $this->ciment, 10);
        $this->approvisionner($a2, $this->ciment, 20);

        $this->assertSame(10, $this->stock($a1, $this->ciment));
        $this->assertSame(20, $this->stock($a2, $this->ciment));

        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);
        $this->assertEqualsCanonicalizing([$this->madina->id, $a1->id, $a2->id], $jean->accessibleStoreIds());

        // Un stock interne doit être rattaché à un magasin, et pas en boucle.
        $this->actingAs($this->admin)->put(route('boutiques.update', $this->madina->id), [
            'store_name' => $this->madina->store_name, 'place_id' => $this->lieu->id, 'user_id' => $this->admin->id,
            'status' => '1', 'description' => 'x', 'type' => 'magasin', 'parent_id' => $a1->id,
        ])->assertSessionHasErrors('parent_id');
    }

    // ------------------------------------------------------------------
    // 4, 5, 13 : vendeurs et restrictions
    // ------------------------------------------------------------------

    public function test_04_creation_vendeur_affecte_a_un_magasin_existant_ou_nouveau(): void
    {
        $vendeurRole = Role::where('slug', 'user')->value('id');
        $base = ['phone' => '620000002', 'password' => 'password1', 'password_confirmation' => 'password1', 'role_id' => $vendeurRole];

        $this->actingAs($this->admin)->post(route('enregistrer'), $base + [
            'name' => 'Jean', 'username' => 'jean', 'email' => 'jean@example.com',
            'store_mode' => 'existing', 'store_id' => $this->madina->id,
        ])->assertRedirect(route('users.index'));

        $jean = User::where('email', 'jean@example.com')->first();
        $this->assertSame($this->madina->id, $jean->assignedStoreId());
        $this->assertTrue($jean->isStoreScoped());
        $this->assertTrue($jean->hasPermission('sales.create'));
        $this->assertFalse($jean->hasPermission('stock.adjust'));
        $this->assertFalse($jean->hasPermission('transfers.create'));

        // Nouveau magasin créé depuis le formulaire, vendeur affecté automatiquement.
        $this->actingAs($this->admin)->post(route('enregistrer'), $base + [
            'name' => 'Awa', 'username' => 'awa', 'email' => 'awa@example.com',
            'store_mode' => 'new', 'new_store' => ['store_name' => 'TELICO — Magasin C', 'place_id' => $this->lieu->id],
        ])->assertRedirect(route('users.index'));

        $awa = User::where('email', 'awa@example.com')->first();
        $magasinC = Store::where('store_name', 'TELICO — Magasin C')->firstOrFail();
        $this->assertSame($magasinC->id, $awa->assignedStoreId());

        // Un magasin peut avoir plusieurs vendeurs.
        $this->user('Paul', User::ROLE_STORE_MANAGER, $this->madina->id);
        $this->assertSame(2, $this->madina->users()->count());
    }

    public function test_05_changement_de_magasin_change_les_acces_et_garde_l_historique(): void
    {
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);
        $this->approvisionner($this->madina, $this->ciment, 100);
        $this->approvisionner($this->magasinB, $this->ciment, 100);

        $this->actingAs($this->admin)->put(route('updateUser', $jean->id), [
            'name' => $jean->name, 'username' => $jean->username, 'phone' => $jean->phone, 'email' => $jean->email,
            'store_mode' => 'existing', 'store_id' => $this->magasinB->id,
        ])->assertRedirect();

        $jean = $jean->fresh();
        $this->assertSame($this->magasinB->id, $jean->assignedStoreId());
        $this->assertSame(2, StoreAssignment::where('user_id', $jean->id)->count());
        $this->assertNotNull(StoreAssignment::where('user_id', $jean->id)->where('store_id', $this->madina->id)->value('ended_at'));

        $this->actingAs($jean)->get(route('stock.show', $this->madina))->assertForbidden();
        $this->actingAs($jean)->get(route('stock.show', $this->magasinB))->assertOk();
    }

    public function test_13_vendeur_ne_peut_ni_voir_ni_vendre_le_stock_d_un_autre_magasin(): void
    {
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);
        $this->approvisionner($this->magasinB, $this->ciment, 100);

        $this->actingAs($jean)->get(route('stock.show', $this->magasinB))->assertForbidden();

        // Tentative de contournement : store_id d'un autre magasin envoyé à la main.
        $this->vendre($jean, $this->madina, [[$this->ciment, 5, $this->magasinB]])->assertSessionHas('error');
        $this->vendre($jean, $this->magasinB, [[$this->ciment, 5, null]])->assertSessionHas('error');
        $this->assertSame(100, $this->stock($this->magasinB, $this->ciment));
        $this->assertSame(0, Sale::count());

        // Journal et vue globale limités à son périmètre.
        $this->actingAs($jean)->get(route('stock.index'))->assertOk()->assertDontSee('TELICO — Magasin B');
        $this->actingAs($jean)->get(route('stock.movements'))->assertForbidden();
        $this->actingAs($jean)->post(route('stock.adjust'), [])->assertForbidden();
        $this->actingAs($jean)->post(route('transfers.store'), [])->assertForbidden();
    }

    // ------------------------------------------------------------------
    // 6, 7 : achats
    // ------------------------------------------------------------------

    private function acheter(Store $destination, Product $produit, int $qte, ?Partner $fournisseur = null)
    {
        return $this->actingAs($this->admin)->post(route('purchase-orders.store'), [
            'reference' => 'PO-TEST-' . uniqid(), 'store_id' => $destination->id, 'partner_id' => $fournisseur?->id,
            'origin' => 'guinee', 'date_emis' => now()->toDateString(), 'date_recu' => now()->toDateString(),
            'lines' => [['product_id' => $produit->id, 'quantity' => $qte, 'unit_price_foreign' => 1000]],
        ]);
    }

    public function test_06_achat_receptionne_exactement_a_l_emplacement_choisi(): void
    {
        $fournisseur = Partner::create(['name' => 'Fournisseur X']);

        $this->acheter($this->entrepot5, $this->ciment, 1000, $fournisseur)->assertRedirect();

        $this->assertSame(1000, $this->stock($this->entrepot5, $this->ciment));
        $this->assertSame(0, $this->stock($this->principal, $this->ciment));
        $this->assertSame(0, $this->stock($this->madina, $this->ciment));

        $mouvement = StockMovement::where('type', StockMovement::PURCHASE)->firstOrFail();
        $this->assertSame($this->entrepot5->id, (int) $mouvement->store_id);
        $this->assertSame($fournisseur->id, (int) $mouvement->supplier_id);
    }

    public function test_07_fournisseur_cree_depuis_le_workflow_d_achat(): void
    {
        $reponse = $this->actingAs($this->admin)->postJson(route('pos.storePartner'), ['name' => 'Nouveau fournisseur', 'phone' => '621']);
        $reponse->assertOk()->assertJson(['success' => true]);
        $fournisseur = Partner::findOrFail($reponse->json('partner.id'));

        $this->acheter($this->entrepot5, $this->ciment, 10, $fournisseur)->assertRedirect();

        $this->assertSame($fournisseur->id, (int) \App\Models\PurchaseOrder::latest('id')->value('partner_id'));
        $this->assertSame(1, $fournisseur->purchaseOrders()->count());
    }

    public function test_changer_la_destination_d_un_achat_deplace_le_stock_au_bon_endroit(): void
    {
        $this->acheter($this->entrepot5, $this->ciment, 100)->assertRedirect();
        $order = \App\Models\PurchaseOrder::latest('id')->first();

        $this->actingAs($this->admin)->put(route('purchase-orders.update', $order->id), [
            'store_id' => $this->madina->id, 'origin' => 'guinee', 'date_emis' => now()->toDateString(), 'date_recu' => now()->toDateString(),
            'lines' => [['product_id' => $this->ciment->id, 'quantity' => 100, 'unit_price_foreign' => 1000]],
        ])->assertRedirect();

        $this->assertSame(0, $this->stock($this->entrepot5, $this->ciment));
        $this->assertSame(100, $this->stock($this->madina, $this->ciment));
    }

    // ------------------------------------------------------------------
    // 8 à 12 : ventes, transferts, commandes
    // ------------------------------------------------------------------

    public function test_08_vente_directe_depuis_un_entrepot(): void
    {
        $this->approvisionner($this->entrepot5, $this->ciment, 2000);
        $vendeur = $this->user('Vendeur siège', User::ROLE_STORE_MANAGER, $this->principal->id);

        $this->vendre($vendeur, $this->principal, [[$this->ciment, 500, $this->entrepot5]])->assertRedirect(route('factures.index'));

        $this->assertSame(1500, $this->stock($this->entrepot5, $this->ciment));
        $vente = Sale::firstOrFail();
        $this->assertSame($this->entrepot5->id, (int) $vente->store_id);
        $this->assertSame($vendeur->id, (int) $vente->user_id);
        $facture = Facture::where('numero_facture', $vente->numeroFacture)->firstOrFail();
        $this->assertSame($this->client->id, (int) $facture->customer_id);
        $this->assertSame($vendeur->id, (int) $facture->user_id);
        $this->assertSame(Facture::CHANNEL_POS, $facture->channel);
    }

    private function transferer(User $par, Store $de, Store $vers, Product $produit, int $qte): StockTransfer
    {
        $this->actingAs($par)->post(route('transfers.store'), [
            'product_id' => $produit->id, 'from_store_id' => $de->id, 'to_store_id' => $vers->id, 'quantity' => $qte,
        ])->assertSessionHas('success');

        return StockTransfer::latest('id')->firstOrFail();
    }

    public function test_09_transfert_entrepot_vers_magasin_avec_circuit_complet(): void
    {
        $this->approvisionner($this->entrepot5, $this->carton, 1000);
        $magasinier = $this->user('Magasinier', 'magasinier', $this->madina->id);
        $responsable = $this->user('Responsable', 'responsable', $this->madina->id);

        $transfert = $this->transferer($magasinier, $this->entrepot5, $this->madina, $this->carton, 300);
        $this->assertSame(StockTransfer::PENDING, $transfert->status);
        $this->assertSame(1000, $this->stock($this->entrepot5, $this->carton));

        // Le magasinier ne peut pas autoriser.
        $this->actingAs($magasinier)->post(route('transfers.approve', $transfert))->assertForbidden();
        $this->actingAs($responsable)->post(route('transfers.approve', $transfert))->assertRedirect();
        // Expédition : la source (Entrepôt 5) n'est pas dans le périmètre de Madina.
        $this->actingAs($magasinier)->post(route('transfers.ship', $transfert))->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('transfers.ship', $transfert))->assertSessionHas('success');
        $this->assertSame(700, $this->stock($this->entrepot5, $this->carton));
        $this->assertSame(0, $this->stock($this->madina, $this->carton));
        // Réception par le magasinier de la destination.
        $this->actingAs($magasinier)->post(route('transfers.receive', $transfert))->assertSessionHas('success');

        $transfert->refresh();
        $this->assertSame(StockTransfer::COMPLETED, $transfert->status);
        $this->assertSame(300, $this->stock($this->madina, $this->carton));
        $this->assertSame($magasinier->id, (int) $transfert->requested_by);
        $this->assertSame($responsable->id, (int) $transfert->approved_by);
        $this->assertSame($this->admin->id, (int) $transfert->shipped_by);
        $this->assertSame($magasinier->id, (int) $transfert->received_by);
        $this->assertEquals([-300, 300], StockMovement::where('document_ref', $transfert->reference)->orderBy('id')->pluck('quantity')->all());
    }

    public function test_09b_tous_les_sens_de_transfert_et_execution_immediate(): void
    {
        $entrepot2 = $this->store('Entrepôt 2', ['type' => Store::TYPE_ENTREPOT]);
        $this->approvisionner($entrepot2, $this->fer, 100);

        foreach ([[$entrepot2, $this->entrepot5], [$this->entrepot5, $this->madina], [$this->madina, $this->magasinB], [$this->magasinB, $entrepot2]] as [$de, $vers]) {
            $this->actingAs($this->admin)->post(route('transfers.store'), [
                'product_id' => $this->fer->id, 'from_store_id' => $de->id, 'to_store_id' => $vers->id, 'quantity' => 10, 'execute_now' => 1,
            ])->assertSessionHas('success');
        }

        $this->assertSame(100, $this->stock($entrepot2, $this->fer));
        $this->assertSame(0, StockTransfer::where('status', '!=', StockTransfer::COMPLETED)->count());
    }

    public function test_10_vente_depuis_un_magasin(): void
    {
        $this->approvisionner($this->madina, $this->carton, 300);
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);

        $this->vendre($jean, $this->madina, [[$this->carton, 50, null]])->assertRedirect(route('factures.index'));

        $this->assertSame(250, $this->stock($this->madina, $this->carton));
    }

    public function test_11_vente_multi_emplacements(): void
    {
        $entrepot2 = $this->store('Entrepôt 2', ['type' => Store::TYPE_ENTREPOT, 'parent_id' => $this->principal->id]);
        $this->approvisionner($this->principal, $this->ciment, 10);
        $this->approvisionner($this->entrepot5, $this->carton, 20);
        $this->approvisionner($entrepot2, $this->fer, 30);
        $vendeur = $this->user('Vendeur siège', User::ROLE_STORE_MANAGER, $this->principal->id);

        $this->vendre($vendeur, $this->principal, [
            [$this->ciment, 5, $this->principal], [$this->carton, 10, $this->entrepot5], [$this->fer, 3, $entrepot2],
        ])->assertRedirect(route('factures.index'));

        $this->assertSame(5, $this->stock($this->principal, $this->ciment));
        $this->assertSame(10, $this->stock($this->entrepot5, $this->carton));
        $this->assertSame(27, $this->stock($entrepot2, $this->fer));
        $this->assertSame(1, Facture::count());
        $this->assertEqualsCanonicalizing(
            [$this->principal->id, $this->entrepot5->id, $entrepot2->id],
            Sale::pluck('store_id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_12_stock_insuffisant_refuse_toute_la_vente_et_toute_la_commande(): void
    {
        $this->approvisionner($this->madina, $this->ciment, 100);
        $this->approvisionner($this->entrepot5, $this->carton, 10);

        // Vente : une ligne en défaut → rien n'est enregistré ni débité.
        $this->vendre($this->admin, $this->madina, [[$this->ciment, 50, $this->madina], [$this->carton, 15, $this->entrepot5]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Stock insuffisant pour Carton carreaux à Entrepôt 5. Disponible : 10 pièces. Demandé : 15 pièces.'));
        $this->assertSame(100, $this->stock($this->madina, $this->ciment));
        $this->assertSame(10, $this->stock($this->entrepot5, $this->carton));
        $this->assertSame(0, Sale::count() + Facture::count() + StockMovement::where('type', StockMovement::SALE)->count());

        // Commande e-commerce : un emplacement par article, même règle.
        $acheteur = $this->user('Client web', User::ROLE_CUSTOMER);
        $adresse = \App\Models\DeliveryAddress::create(['user_id' => $acheteur->id, 'full_address' => 'Madina', 'city' => 'Conakry', 'phone' => '620']);
        $order = Order::create(['user_id' => $acheteur->id, 'delivery_address_id' => $adresse->id, 'total_amount' => 1000, 'status' => 'pending']);
        $a = OrderItem::create(['order_id' => $order->id, 'product_id' => $this->ciment->id, 'quantity' => 10, 'price' => 1500]);
        $b = OrderItem::create(['order_id' => $order->id, 'product_id' => $this->carton->id, 'quantity' => 15, 'price' => 1500]);

        $this->actingAs($this->admin)->postJson(route('admin.orders.approve', $order), [
            'store_id' => $this->madina->id, 'locations' => [$a->id => $this->madina->id, $b->id => $this->entrepot5->id],
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Erreur : Stock insuffisant pour Carton carreaux à Entrepôt 5. Disponible : 10 pièces. Demandé : 15 pièces.']);

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(100, $this->stock($this->madina, $this->ciment));

        // Avec assez de stock, chaque article sort de son propre emplacement.
        $this->approvisionner($this->entrepot5, $this->carton, 5);
        $this->actingAs($this->admin)->postJson(route('admin.orders.approve', $order), [
            'store_id' => $this->madina->id, 'locations' => [$a->id => $this->madina->id, $b->id => $this->entrepot5->id],
        ])->assertOk();
        $this->assertSame(90, $this->stock($this->madina, $this->ciment));
        $this->assertSame(0, $this->stock($this->entrepot5, $this->carton));
        $this->assertSame($this->entrepot5->id, (int) $b->fresh()->store_id);
    }

    public function test_commande_approuvee_avec_un_emplacement_par_produit_sans_boutique(): void
    {
        $this->approvisionner($this->madina, $this->ciment, 20);
        $this->approvisionner($this->entrepot5, $this->carton, 20);
        $acheteur = $this->user('Client web', User::ROLE_CUSTOMER);
        $adresse = \App\Models\DeliveryAddress::create(['user_id' => $acheteur->id, 'full_address' => 'Madina', 'city' => 'Conakry', 'phone' => '620']);
        $order = Order::create(['user_id' => $acheteur->id, 'delivery_address_id' => $adresse->id, 'total_amount' => 1000, 'status' => 'pending']);
        $a = OrderItem::create(['order_id' => $order->id, 'product_id' => $this->ciment->id, 'quantity' => 4, 'price' => 1500]);
        $b = OrderItem::create(['order_id' => $order->id, 'product_id' => $this->carton->id, 'quantity' => 4, 'price' => 1500]);

        // Un emplacement manquant est refusé avec le nom du produit.
        $this->actingAs($this->admin)->postJson(route('admin.orders.approve', $order), ['locations' => [$a->id => $this->madina->id]])
            ->assertStatus(422)->assertJsonFragment(['message' => "Choisissez l'emplacement du stock pour « Carton carreaux »."]);

        $this->actingAs($this->admin)->postJson(route('admin.orders.approve', $order), [
            'locations' => [$a->id => $this->madina->id, $b->id => $this->entrepot5->id],
        ])->assertOk();

        $this->assertSame(16, $this->stock($this->madina, $this->ciment));
        $this->assertSame(16, $this->stock($this->entrepot5, $this->carton));
        // Facturée par le magasin du premier emplacement.
        $this->assertSame($this->madina->id, (int) Facture::where('channel', Facture::CHANNEL_ECOMMERCE)->value('store_id'));
    }

    public function test_validation_d_un_devis_multi_emplacements(): void
    {
        $this->approvisionner($this->madina, $this->ciment, 10);
        $this->approvisionner($this->entrepot5, $this->carton, 10);

        $devis = \App\Models\Devi::create(['store_id' => $this->madina->id, 'customer_id' => $this->client->id, 'total_amount' => 0, 'status' => 'accepted', 'created_by' => $this->admin->id]);
        \App\Models\DeviLine::create(['devis_id' => $devis->id, 'product_id' => $this->ciment->id, 'quantity' => 4, 'unit_price' => 1500, 'total_price' => 6000]);
        \App\Models\DeviLine::create(['devis_id' => $devis->id, 'product_id' => $this->carton->id, 'store_id' => $this->entrepot5->id, 'quantity' => 6, 'unit_price' => 1500, 'total_price' => 9000]);

        $this->actingAs($this->admin)->post(route('devis.validate', $devis->id))->assertRedirect();

        $this->assertSame(6, $this->stock($this->madina, $this->ciment));
        $this->assertSame(4, $this->stock($this->entrepot5, $this->carton));
        $facture = Facture::where('channel', Facture::CHANNEL_DEVIS)->firstOrFail();
        $this->assertEquals(15000, (float) $facture->montant_total);
        $this->assertSame('validated', $devis->fresh()->status);
    }

    // ------------------------------------------------------------------
    // 14 à 16 : ventes flash, rapports, traçabilité
    // ------------------------------------------------------------------

    public function test_14_ventes_flash_par_vendeur(): void
    {
        $this->approvisionner($this->madina, $this->ciment, 100);
        $this->approvisionner($this->magasinB, $this->ciment, 100);
        $jean = $this->user('Jean Flash', User::ROLE_STORE_MANAGER, $this->madina->id);
        $awa = $this->user('Awa Flash', User::ROLE_STORE_MANAGER, $this->magasinB->id);

        $this->vendre($jean, $this->madina, [[$this->ciment, 2, null]]);
        $this->vendre($jean, $this->madina, [[$this->ciment, 3, null]]);
        $this->vendre($awa, $this->magasinB, [[$this->ciment, 4, null]]);

        $page = $this->actingAs($this->admin)->get(route('reports.flash', ['period' => 'daily']))->assertOk();
        $page->assertSee('Jean Flash')->assertSee('Awa Flash');
        $this->assertSame(3, $page->viewData('totals')['nb']);
        $this->assertSame(9, $page->viewData('totals')['qty']);
        $parVendeur = $page->viewData('byVendeur')->keyBy('user_id');
        $this->assertSame(2, (int) $parVendeur[$jean->id]->nb);
        $this->assertEquals(7500, (float) $parVendeur[$jean->id]->ca);

        // Un vendeur ne voit que son magasin.
        $vuJean = $this->actingAs($jean)->get(route('reports.flash', ['period' => 'daily']))->assertOk();
        $this->assertSame(2, $vuJean->viewData('totals')['nb']);
    }

    public function test_14b_vente_tierce_comptee_dans_les_ventes_flash(): void
    {
        $ami = Partner::create(['name' => 'Ami fournisseur']);
        $jean = $this->user('Jean Tierce', User::ROLE_STORE_MANAGER, $this->madina->id);

        $this->actingAs($jean)->postJson(route('pos.storeQuickThirdParty'), [
            'customer_id' => $this->client->id, 'avance' => 0,
            'lignes' => [['libelle' => 'Tuyau PVC 50 mm', 'sku' => 'TRC-001', 'prix' => 12000,
                'purchase_price_from_partner' => 10000, 'partner_id' => $ami->id, 'quantity' => 3]],
        ])->assertOk()->assertJson(['success' => true]);

        $page = $this->actingAs($this->admin)->get(route('reports.flash', ['period' => 'daily']))->assertOk();
        $this->assertSame(1, $page->viewData('totals')['nb']);
        $this->assertSame(3, $page->viewData('totals')['qty']);
        $this->assertEquals(36000, $page->viewData('totals')['ca']);
        $this->assertEquals(6000, $page->viewData('totals')['marge']);
        $page->assertSee('Jean Tierce')->assertSee('Vente tierce');

        $this->assertSame(1, $this->actingAs($jean)->get(route('reports.flash', ['period' => 'daily']))->viewData('totals')['nb']);
    }

    public function test_15_rapports_jour_semaine_mois_annee(): void
    {
        $this->approvisionner($this->madina, $this->ciment, 100);
        $this->vendre($this->admin, $this->madina, [[$this->ciment, 2, null]]);

        foreach (['daily', 'weekly', 'monthly', 'yearly'] as $period) {
            $flash = $this->actingAs($this->admin)->get(route('reports.flash', ['period' => $period]))->assertOk();
            $this->assertSame(1, $flash->viewData('totals')['nb'], $period);

            $ventes = $this->actingAs($this->admin)->get(route('reports.sales', ['period' => $period, 'store_id' => $this->madina->id]))->assertOk();
            $this->assertSame(2, (int) $ventes->viewData('totalQuantity'), $period);
        }

        $hier = $this->actingAs($this->admin)->get(route('reports.flash', ['period' => 'daily', 'date' => now()->subDays(2)->toDateString()]));
        $this->assertSame(0, $hier->viewData('totals')['nb']);
    }

    public function test_16_tracabilite_achat_transfert_vente_client(): void
    {
        $fournisseur = Partner::create(['name' => 'Cimenterie X']);
        $this->acheter($this->entrepot5, $this->ciment, 500, $fournisseur)->assertRedirect();
        $this->actingAs($this->admin)->post(route('transfers.store'), [
            'product_id' => $this->ciment->id, 'from_store_id' => $this->entrepot5->id, 'to_store_id' => $this->madina->id, 'quantity' => 100, 'execute_now' => 1,
        ]);
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);
        $this->vendre($jean, $this->madina, [[$this->ciment, 50, null]]);

        $chaine = StockMovement::where('product_id', $this->ciment->id)->orderBy('id')->get();
        $this->assertSame(
            [StockMovement::PURCHASE, StockMovement::TRANSFER_OUT, StockMovement::TRANSFER_IN, StockMovement::SALE],
            $chaine->pluck('type')->all()
        );
        $this->assertSame($fournisseur->id, (int) $chaine[0]->supplier_id);
        $this->assertSame($this->madina->id, (int) $chaine[1]->counterpart_store_id);
        $this->assertSame($this->client->id, (int) $chaine[3]->customer_id);
        $this->assertSame($jean->id, (int) $chaine[3]->user_id);
        $this->assertEquals(1500, (float) $chaine[3]->unit_price);

        $this->actingAs($this->admin)->get(route('stock.trace', ['product_id' => $this->ciment->id]))
            ->assertOk()->assertSee('Cimenterie X')->assertSee('Client X')->assertSee('Transfert entrant');

        $this->assertSame(400, $this->stock($this->entrepot5, $this->ciment));
        $this->assertSame(50, $this->stock($this->madina, $this->ciment));
    }

    public function test_journal_non_modifiable_et_inventaire_trace(): void
    {
        $this->approvisionner($this->madina, $this->ciment, 100);

        $this->actingAs($this->admin)->post(route('stock.adjust'), [
            'store_id' => $this->madina->id, 'product_id' => $this->ciment->id, 'counted' => 97, 'type' => 'INVENTORY', 'reason' => 'Inventaire mensuel',
        ])->assertSessionHas('success');

        $this->assertSame(97, $this->stock($this->madina, $this->ciment));
        $inventaire = StockMovement::where('type', StockMovement::INVENTORY)->firstOrFail();
        $this->assertSame(-3, (int) $inventaire->quantity);
        $this->assertSame('Inventaire mensuel', $inventaire->reason);

        $this->expectException(\LogicException::class);
        $inventaire->update(['quantity' => 0]);
    }

    public function test_magasin_de_vente_deduit_de_l_origine_quand_il_n_est_pas_choisi(): void
    {
        $this->approvisionner($this->entrepot5, $this->ciment, 100);

        $this->actingAs($this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-AUTO', 'customer_id' => $this->client->id, 'avance' => 0, 'paid_by' => 'cash',
            'sales' => [1 => ['product_id' => $this->ciment->id, 'quantity' => 10, 'prix' => 1500, 'store_id' => $this->entrepot5->id, 'is_third_party' => '0']],
        ])->assertRedirect(route('factures.index'));

        // Le stock sort de l'Entrepôt 5 ; la facture est émise par son magasin de rattachement.
        $this->assertSame(90, $this->stock($this->entrepot5, $this->ciment));
        $this->assertSame($this->principal->id, (int) Facture::firstOrFail()->store_id);
        $this->assertSame($this->entrepot5->id, (int) Sale::firstOrFail()->store_id);
    }

    public function test_sections_le_proprietaire_entre_dans_une_section_et_en_ressort(): void
    {
        $this->approvisionner($this->entrepot5, $this->ciment, 100);
        $this->approvisionner($this->madina, $this->ciment, 30);

        // Page des sections : une carte par emplacement de premier niveau ;
        // l'Entrepôt 5 est dans la section du magasin principal.
        $page = $this->actingAs($this->admin)->get(route('sections.index'))->assertOk();
        $cartes = $page->viewData('sections')->keyBy(fn ($s) => $s->store->id);
        $this->assertCount(3, $cartes);
        $this->assertSame(100, $cartes[$this->principal->id]->stock);
        $this->assertTrue($cartes[$this->principal->id]->locations->contains('id', $this->entrepot5->id));

        // Entrer dans la section Madina : les données se limitent à Madina.
        $this->actingAs($this->admin)->post(route('sections.switch'), ['section_id' => $this->madina->id])->assertRedirect(route('home'));
        $this->actingAs($this->admin)->get(route('stock.index'))->assertOk()
            ->assertSee('TELICO — Magasin Madina')->assertDontSee('Entrepôt 5');
        // … mais la gestion (liste des magasins) reste complète pour le propriétaire.
        $this->actingAs($this->admin)->get(route('boutiques.index'))->assertOk()->assertSee('Entrepôt 5');
        // Une vente dans la section est facturée par la section.
        $this->vendre($this->admin, $this->magasinB, [[$this->ciment, 1, null]]);
        $this->assertSame($this->madina->id, (int) Facture::latest('id')->value('store_id'));

        // Retour à la vue globale.
        $this->actingAs($this->admin)->post(route('sections.switch'), ['section_id' => ''])->assertRedirect(route('home'));
        $this->actingAs($this->admin)->get(route('stock.index'))->assertOk()->assertSee('Entrepôt 5');
    }

    public function test_sections_un_employe_reste_dans_sa_section(): void
    {
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);

        $page = $this->actingAs($jean)->get(route('sections.index'))->assertOk();
        $this->assertSame([$this->madina->id], $page->viewData('sections')->map(fn ($s) => $s->store->id)->all());
        $this->actingAs($jean)->post(route('sections.switch'), ['section_id' => $this->magasinB->id])->assertForbidden();
        $this->assertSame($this->madina->id, $jean->fresh()->currentSection()->id);
    }

    private function commandeEnLigne(array $lignes): Order
    {
        $acheteur = $this->user('Client web ' . uniqid(), User::ROLE_CUSTOMER);
        $adresse = \App\Models\DeliveryAddress::create(['user_id' => $acheteur->id, 'full_address' => 'Madina', 'city' => 'Conakry', 'phone' => '620']);
        $order = Order::create(['user_id' => $acheteur->id, 'delivery_address_id' => $adresse->id, 'total_amount' => 1000, 'status' => 'pending']);
        foreach ($lignes as [$produit, $qte]) {
            OrderItem::create(['order_id' => $order->id, 'product_id' => $produit->id, 'quantity' => $qte, 'price' => 1500]);
        }

        return $order;
    }

    public function test_commande_en_ligne_traitee_au_siege_par_defaut(): void
    {
        $order = $this->commandeEnLigne([[$this->ciment, 2]]);
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);

        $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk()->assertSee('#' . $order->id);

        // Non attribuée : invisible pour les boutiques.
        $liste = $this->actingAs($jean)->get(route('admin.orders.index'))->assertOk();
        $this->assertSame(0, $liste->viewData('orders')->total());
        $this->actingAs($jean)->get(route('admin.orders.show', $order))->assertForbidden();
        $this->actingAs($jean)->get(route('admin.orders.stock.check', $order))->assertForbidden();

        // Seul un profil qui a orders.assign peut attribuer.
        $this->actingAs($jean)->post(route('admin.orders.assign', $order), ['store_id' => $this->madina->id])->assertForbidden();
    }

    public function test_commande_attribuee_a_une_boutique_visible_uniquement_par_elle(): void
    {
        $this->approvisionner($this->madina, $this->ciment, 10);
        $this->approvisionner($this->entrepot5, $this->ciment, 100);
        $order = $this->commandeEnLigne([[$this->ciment, 4]]);
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);
        $responsable = $this->user('Responsable Madina', 'responsable', $this->madina->id);
        $awa = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->magasinB->id);

        // La liste indique quelles boutiques ont tout le stock.
        $liste = $this->actingAs($this->admin)->get(route('admin.orders.index'))->assertOk();
        $this->assertTrue($liste->viewData('couverture')[$order->id][$this->madina->id]);
        $this->assertFalse($liste->viewData('couverture')[$order->id][$this->magasinB->id]);

        $this->actingAs($this->admin)->post(route('admin.orders.assign', $order), ['store_id' => $this->madina->id])->assertSessionHas('success');
        $this->assertSame($this->madina->id, (int) $order->fresh()->store_id);
        $this->assertSame(1, $jean->notifications()->count());
        $this->assertSame(0, $awa->notifications()->count());

        // Madina la voit, la boutique B non.
        $this->assertSame(1, $this->actingAs($jean)->get(route('admin.orders.index'))->viewData('orders')->total());
        $this->assertSame(0, $this->actingAs($awa)->get(route('admin.orders.index'))->viewData('orders')->total());
        $this->actingAs($awa)->get(route('admin.orders.show', $order))->assertForbidden();

        // La boutique traite la commande — même si elle n'a que des vendeurs —
        // mais uniquement depuis son propre stock.
        $item = $order->items()->first();
        $this->assertTrue($responsable->hasPermission('orders.validate'));
        $this->actingAs($jean)->postJson(route('admin.orders.approve', $order), ['locations' => [$item->id => $this->entrepot5->id]])->assertStatus(422);
        $this->actingAs($awa)->postJson(route('admin.orders.approve', $order), ['locations' => [$item->id => $this->magasinB->id]])->assertForbidden();
        $this->actingAs($jean)->postJson(route('admin.orders.approve', $order), ['locations' => [$item->id => $this->madina->id]])->assertOk();

        $this->assertSame(6, $this->stock($this->madina, $this->ciment));
        $this->assertSame($this->madina->id, (int) Facture::where('channel', Facture::CHANNEL_ECOMMERCE)->value('store_id'));

        // Le siège peut toujours reprendre une commande en attente.
        $autre = $this->commandeEnLigne([[$this->ciment, 1]]);
        $this->actingAs($this->admin)->post(route('admin.orders.assign', $autre), ['store_id' => $this->madina->id]);
        $this->actingAs($this->admin)->post(route('admin.orders.assign', $autre), ['store_id' => ''])->assertSessionHas('success');
        $this->assertNull($autre->fresh()->store_id);
    }

    public function test_le_vendeur_peut_rejeter_une_commande_attribuee_a_sa_boutique(): void
    {
        $order = $this->commandeEnLigne([[$this->ciment, 1]]);
        $siege = $this->commandeEnLigne([[$this->ciment, 1]]);
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);
        $awa = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->magasinB->id);
        $this->actingAs($this->admin)->post(route('admin.orders.assign', $order), ['store_id' => $this->madina->id]);

        // Ni une autre boutique, ni une commande restée au siège.
        $this->actingAs($awa)->postJson(route('admin.orders.reject', $order))->assertForbidden();
        $this->actingAs($jean)->postJson(route('admin.orders.reject', $siege))->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $siege->fresh()->status);

        $this->actingAs($jean)->post(route('admin.orders.reject', $order))->assertRedirect(route('admin.orders.index'));
        $this->assertSame('rejected', $order->fresh()->status);
    }

    public function test_la_boutique_peut_rendre_une_commande_au_siege(): void
    {
        $order = $this->commandeEnLigne([[$this->ciment, 1]]);
        $jean = $this->user('Jean', User::ROLE_STORE_MANAGER, $this->madina->id);
        $awa = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->magasinB->id);
        $this->actingAs($this->admin)->post(route('admin.orders.assign', $order), ['store_id' => $this->madina->id]);

        $this->actingAs($awa)->post(route('admin.orders.release', $order))->assertForbidden();

        $this->actingAs($jean)->post(route('admin.orders.release', $order), ['reason' => 'Pas de stock'])
            ->assertRedirect(route('admin.orders.index'));

        $order->refresh();
        $this->assertNull($order->store_id);
        $this->assertSame('pending', $order->status);
        // Elle a quitté la boutique et le siège est prévenu, avec le motif.
        $this->assertSame(0, $this->actingAs($jean)->get(route('admin.orders.index'))->viewData('orders')->total());
        $message = $this->admin->notifications()->latest()->first()->data['message'];
        $this->assertStringContainsString('rendue au siège par TELICO — Magasin Madina (Jean) : Pas de stock', $message);
    }

    public function test_interet_des_commandes_en_ligne_au_prix_de_revient(): void
    {
        $this->approvisionner($this->madina, $this->ciment, 10);
        $this->approvisionner($this->madina, $this->carton, 10);
        // Ancien « achat simple » à un prix sans rapport (au carton) : ne doit plus faire foi.
        \App\Models\Purchase::create(['product_id' => $this->ciment->id, 'store_id' => $this->madina->id, 'price' => 180000,
            'numeroPurchase' => 'P-1', 'quantity' => 1, 'description' => 'ancien']);
        $this->carton->update(['price_sale' => 0]); // coût inconnu
        $order = $this->commandeEnLigne([[$this->ciment, 2], [$this->carton, 3]]);

        $this->actingAs($this->admin)->postJson(route('admin.orders.approve', $order), [
            'store_id' => $this->madina->id,
        ])->assertOk();

        // Ciment : (1500 − 1000) × 2 ; carton sans coût connu : 0, pas 100 % du prix.
        $this->assertEquals(1000, (float) Sale::where('product_id', $this->ciment->id)->value('interet'));
        $this->assertEquals(0, (float) Sale::where('product_id', $this->carton->id)->value('interet'));
    }

    public function test_vendeur_sans_magasin_ne_voit_rien(): void
    {
        $orphelin = $this->user('Sans magasin', User::ROLE_STORE_MANAGER);
        $this->approvisionner($this->madina, $this->ciment, 10);

        $this->assertSame([], $orphelin->accessibleStoreIds());
        $this->assertFalse($orphelin->canAccessStore($this->madina->id));
        $this->vendre($orphelin, $this->madina, [[$this->ciment, 1, null]])->assertSessionHas('error');
        $this->assertSame(10, $this->stock($this->madina, $this->ciment));
    }
}
