<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Facture;
use App\Models\Payment;
use App\Models\Place;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\TransactionFacture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Reçus de paiement et de transaction : un employé rattaché à un magasin ne
 * voit que ceux des factures de son périmètre, comme pour les factures et
 * les transferts. La vue globale voit tout.
 */
class ReceiptScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendeurA;
    private User $vendeurB;
    private Store $magasinA;
    private Store $magasinB;
    private Customer $client;
    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->magasinA = $this->magasin('Madina', true);
        $this->magasinB = $this->magasin('Kaloum', false);
        $this->vendeurA = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->magasinA->id);
        $this->vendeurB = $this->user('Binta', User::ROLE_STORE_MANAGER, $this->magasinB->id);
        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620', 'address' => 'Madina']);
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x', 'price_sale' => 45000, 'price' => 60000]);
        foreach ([$this->magasinA, $this->magasinB] as $m) {
            StoreProduct::create(['store_id' => $m->id, 'product_id' => $this->ciment->id, 'quantity' => 100]);
        }
    }

    private function magasin(string $nom, bool $principal): Store
    {
        $lieu = Place::firstOrFail();

        return Store::create(['store_name' => $nom, 'place_id' => $lieu->id, 'user_id' => $this->admin->id ?? 1,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => $principal]);
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

    /** Vente à crédit (avance 1 000) dans le magasin du vendeur. */
    private function venteACredit(User $vendeur, Store $magasin): Facture
    {
        $this->actingAs($vendeur)->post(route('sales.store'), [
            'numeroFacture' => 'F-' . uniqid(), 'store_id' => $magasin->id, 'customer_id' => $this->client->id,
            'avance' => 1000, 'paid_by' => 'cash',
            'sales' => [1 => ['product_id' => $this->ciment->id, 'quantity' => 1, 'prix' => 60000, 'store_id' => $magasin->id]],
        ])->assertSessionHas('success');

        return Facture::latest('id')->firstOrFail();
    }

    public function test_recu_de_paiement_et_liste_des_paiements_limites_au_magasin(): void
    {
        $factureA = $this->venteACredit($this->vendeurA, $this->magasinA);
        $factureB = $this->venteACredit($this->vendeurB, $this->magasinB);
        $paiementA = Payment::where('facture_id', $factureA->id)->firstOrFail();
        $paiementB = Payment::where('facture_id', $factureB->id)->firstOrFail();

        // Reçus : le sien oui, celui de l'autre magasin non ; la vue globale voit tout.
        $this->actingAs($this->vendeurA)->get(route('receipts.payments.show', $paiementA))->assertOk();
        $this->actingAs($this->vendeurA)->get(route('receipts.payments.show', $paiementB))->assertForbidden();
        $this->actingAs($this->admin)->get(route('receipts.payments.show', $paiementB))->assertOk();

        // Liste des paiements : seulement ceux de son magasin.
        $liste = $this->actingAs($this->vendeurA)->get(route('payments.index', ['period' => 'yearly']))->assertOk();
        $this->assertSame([$paiementA->id], $liste->viewData('dataTable')->pluck('id')->all());
        $this->assertSame([$factureA->id], $liste->viewData('factures')->pluck('id')->all());
        $this->assertCount(2, $this->actingAs($this->admin)->get(route('payments.index', ['period' => 'yearly']))->viewData('dataTable'));

        // Paiement d'une facture d'un autre magasin : écrans et enregistrement refusés.
        $this->actingAs($this->vendeurA)->get(route('payments.creation', $factureB->id))->assertForbidden();
        $this->actingAs($this->vendeurA)->get(route('payments.voir', $factureB->id))->assertForbidden();
        $this->actingAs($this->vendeurA)->post(route('payments.store'), [
            'facture_id' => $factureB->id, 'versement' => 1000, 'paid_by' => 'cash',
        ])->assertForbidden();
        $this->assertSame(1, Payment::where('facture_id', $factureB->id)->count());

        // Sa propre facture : autorisé.
        $this->actingAs($this->vendeurA)->get(route('payments.creation', $factureA->id))->assertOk();

        // Export Excel des paiements : même périmètre que la liste.
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->actingAs($this->vendeurA)->get(route('payments.exportExcel', ['period' => 'yearly']))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('paiements.xlsx', function ($export) use ($paiementA) {
            $this->assertSame([$paiementA->id], collect((fn () => $this->payments)->call($export))->pluck('id')->all());

            return true;
        });
    }

    public function test_recu_de_transaction_limite_au_magasin(): void
    {
        $this->venteACredit($this->vendeurA, $this->magasinA);
        $this->venteACredit($this->vendeurB, $this->magasinB);

        // Versement global fait à Kaloum : il ne règle que les factures de Kaloum.
        $this->actingAs($this->vendeurB)->post(route('paiementsClient.store'), [
            'customer_id' => $this->client->id, 'versement' => 10000, 'paid_by' => 'cash',
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error')->assertSessionMissing('fail');
        $versementB = TransactionFacture::firstOrFail();
        $this->assertSame([$this->magasinB->id], $versementB->payments->map(fn ($p) => $p->facture->store_id)->unique()->values()->all());

        $this->actingAs($this->vendeurB)->get(route('receipts.transactions.show', $versementB))->assertOk();
        $this->actingAs($this->vendeurA)->get(route('receipts.transactions.show', $versementB))->assertForbidden();
        $this->actingAs($this->admin)->get(route('receipts.transactions.show', $versementB))->assertOk();

        // Historique des versements du client : le vendeur de Madina ne voit pas celui de Kaloum.
        $this->assertCount(0, $this->actingAs($this->vendeurA)->get(route('paiementsClient.add', $this->client->mark))->viewData('dataTable'));
        $this->assertCount(1, $this->actingAs($this->vendeurB)->get(route('paiementsClient.add', $this->client->mark))->viewData('dataTable'));

        // Ancien versement non rattaché à ses factures : visible si le client a
        // des factures dans le magasin du vendeur.
        $ancien = TransactionFacture::create(['customer_id' => $this->client->id, 'versement' => 500, 'balance' => 2000, 'receipt_number' => 'RCC-ANCIEN']);
        $this->actingAs($this->vendeurA)->get(route('receipts.transactions.show', $ancien))->assertOk();
        $autreClient = Customer::create(['customerName' => 'Client Y', 'mark' => 'CLY', 'tel' => '621', 'address' => 'Kaloum']);
        $sansFacture = TransactionFacture::create(['customer_id' => $autreClient->id, 'versement' => 500, 'balance' => 500, 'receipt_number' => 'RCC-Y']);
        $this->actingAs($this->vendeurA)->get(route('receipts.transactions.show', $sansFacture))->assertForbidden();
    }
}
