<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Facture;
use App\Models\Partner;
use App\Models\PartnerSettlement;
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
 * Vente mixte à la caisse : des produits du stock et des produits que la
 * boutique n'a pas, pris chez un partenaire, sur une seule facture. Le client
 * ne voit pas la provenance ; le rapport des ventes tierces suit le dû et les
 * règlements aux partenaires.
 */
class ThirdPartySaleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $magasin;
    private Customer $client;
    private Partner $ami;
    private Product $ciment;
    private Product $robinet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->magasin = Store::create(['store_name' => 'Madina', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => true]);
        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620', 'address' => 'Madina']);
        $this->ami = Partner::create(['name' => 'Moussa Quincaillerie', 'phone' => '621']);

        // Ciment : en stock. Robinet : au catalogue, mais aucun stock.
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x', 'price_sale' => 45000, 'price' => 60000, 'unite' => 'pcs']);
        StoreProduct::create(['store_id' => $this->magasin->id, 'product_id' => $this->ciment->id, 'quantity' => 100]);
        $this->robinet = Product::create(['libelle' => 'Robinet', 'sku' => 'ROB', 'description' => 'x', 'price_sale' => 0, 'price' => 30000, 'unite' => 'carton', 'pcs_par_carton' => 6]);
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

    /** Vente mixte : 2 sacs de ciment du stock + des produits pris chez Moussa. */
    private function venteMixte(array $lignesTiers, ?User $vendeur = null)
    {
        $sales = [1 => ['product_id' => $this->ciment->id, 'quantity' => 2, 'prix' => 60000, 'store_id' => $this->magasin->id, 'is_third_party' => '0']];
        foreach ($lignesTiers as $i => $ligne) {
            $sales[$i + 2] = $ligne + ['is_third_party' => '1', 'partner_id' => $this->ami->id];
        }

        return $this->actingAs($vendeur ?? $this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-' . uniqid(), 'store_id' => $this->magasin->id, 'customer_id' => $this->client->id,
            'avance' => 0, 'paid_by' => 'cash', 'sales' => $sales,
        ]);
    }

    public function test_la_caisse_propose_tout_le_catalogue_meme_sans_stock(): void
    {
        $page = $this->actingAs($this->admin)->get(route('pos'))->assertOk();
        $page->assertSee('Produit pris chez un partenaire');
        $this->assertContains($this->robinet->id, $page->viewData('produitsTiers')->pluck('id')->all());
    }

    public function test_vente_mixte_sur_une_seule_facture(): void
    {
        // 3 cartons de robinets (catalogue, sans stock) + un produit hors catalogue.
        $this->venteMixte([
            ['product_id' => $this->robinet->id, 'quantity' => 3, 'prix' => 30000, 'purchase_price_from_partner' => 25000],
            ['nouveau_libelle' => 'Mitigeur douche', 'nouvelle_unite' => 'pcs', 'quantity' => 1, 'prix' => 90000, 'purchase_price_from_partner' => 70000],
        ])->assertSessionHas('success');

        $this->assertSame(1, Facture::count());
        $facture = Facture::firstOrFail();
        $this->assertEquals(2 * 60000 + 3 * 30000 + 90000, (float) $facture->montant_total);
        $this->assertSame(3, Sale::where('numeroFacture', $facture->numero_facture)->count());

        // Seul le ciment sort du stock ; aucune ligne de stock pour les produits tiers.
        $this->assertSame(98, (int) StoreProduct::where('product_id', $this->ciment->id)->value('quantity'));
        $this->assertSame(0, StoreProduct::where('product_id', $this->robinet->id)->count());

        $robinets = Sale::where('product_id', $this->robinet->id)->firstOrFail();
        $this->assertTrue((bool) $robinets->is_third_party);
        $this->assertSame('carton', $robinets->unite);
        $this->assertEquals(3 * 5000, $robinets->interet);
        $this->assertEquals(75000, $robinets->du_partenaire);

        // Produit hors catalogue créé avec la vente, à l'unité choisie.
        $mitigeur = Product::where('libelle', 'Mitigeur douche')->firstOrFail();
        $this->assertSame('pcs', $mitigeur->unite);
        $this->assertEquals(70000, (float) $mitigeur->price_sale);
    }

    public function test_mention_interne_et_jamais_sur_le_document_client(): void
    {
        $this->venteMixte([['product_id' => $this->robinet->id, 'quantity' => 3, 'prix' => 30000, 'purchase_price_from_partner' => 25000]]);
        $numero = Facture::firstOrFail()->numero_facture;

        // Fiche facture : mention à l'écran, masquée à l'impression.
        $fiche = $this->actingAs($this->admin)->get(route('factures.show', $numero))->assertOk();
        $fiche->assertSee('Pris chez Moussa Quincaillerie')->assertSee('.mention-interne { display: none !important; }', false);
        // « Télécharger PDF » (fabriqué depuis l'écran) : mentions retirées le temps de la génération.
        $fiche->assertSee('data-html2canvas-ignore="true"', false)->assertSee("element.querySelectorAll('.mention-interne')", false);

        // Bon de sortie (interne) : où récupérer l'article.
        $this->actingAs($this->admin)->get(route('factures.bon-de-sortie', $numero))->assertOk()
            ->assertSee('À récupérer chez le partenaire : Moussa Quincaillerie')
            // … à l'écran seulement : masquée à l'impression et dans le PDF téléchargé.
            ->assertSee('.mention-interne { display: none !important; }', false)
            ->assertSee('body.pdf-mode .mention-interne { display: none !important; }', false)
            ->assertSee('class="mention-interne" data-html2canvas-ignore="true"', false);

        // Bon de commande (document client) : aucune mention.
        $this->actingAs($this->admin)->get(route('factures.bon-de-commande', $numero))->assertOk()
            ->assertDontSee('Moussa')->assertDontSee('25 000');

        // Détail des ventes.
        $this->actingAs($this->admin)->get(route('voirSales', $numero))->assertOk()->assertSee('Pris chez Moussa Quincaillerie');

        // Reçu de paiement : mention à l'écran, retirée du PDF et de l'impression ;
        // le PDF ne coupe plus un reçu trop long pour une page.
        $paiement = \App\Models\Payment::whereHas('facture', fn ($q) => $q->where('numero_facture', $numero))->firstOrFail();
        $this->actingAs($this->admin)->get(route('receipts.payments.show', $paiement))->assertOk()
            ->assertSee('Pris chez Moussa Quincaillerie')
            ->assertSee('@media print { .mention-interne { display: none !important; } }', false)
            ->assertSee("clonedElement.querySelectorAll('.mention-interne').forEach(m => m.remove());", false)
            ->assertSee('if (unePage) {', false);
    }

    public function test_refus_hors_catalogue_sans_partenaire_et_vente_a_perte(): void
    {
        $this->actingAs($this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-X', 'store_id' => $this->magasin->id, 'customer_id' => $this->client->id, 'avance' => 0,
            'sales' => [1 => ['nouveau_libelle' => 'Inconnu', 'quantity' => 1, 'prix' => 1000, 'is_third_party' => '0']],
        ])->assertSessionHas('error');

        $this->venteMixte([['product_id' => $this->robinet->id, 'quantity' => 1, 'prix' => 20000, 'purchase_price_from_partner' => 25000]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Vente à perte'));

        $this->assertSame(0, Facture::count() + Sale::count());
        $this->assertSame(0, Product::where('libelle', 'Inconnu')->count());
    }

    public function test_rapport_des_ventes_tierces_du_et_reglements(): void
    {
        $this->venteMixte([['product_id' => $this->robinet->id, 'quantity' => 3, 'prix' => 30000, 'purchase_price_from_partner' => 25000]]);

        $rapport = $this->actingAs($this->admin)->get(route('reports.tiers', ['period' => 'monthly']))->assertOk();
        $rapport->assertSee('Moussa Quincaillerie')->assertSee('3 cartons')->assertDontSee('Ciment');
        $ligne = $rapport->viewData('partenaires')->first();
        $this->assertEquals(75000, $ligne->du);
        $this->assertEquals(75000, $ligne->solde);
        $this->assertEquals(90000, $rapport->viewData('totals')['ca']);
        $this->assertEquals(15000, $rapport->viewData('totals')['marge']);

        // Règlement partiel, puis tentative de payer plus que le reste dû.
        $this->actingAs($this->admin)->post(route('reports.tiers.settle'), [
            'partner_id' => $this->ami->id, 'amount' => 50000, 'paid_at' => now()->toDateString(), 'mode' => 'cash',
        ])->assertSessionHas('success', fn ($m) => str_contains($m, 'Reste dû : 25 000 FG'));
        $this->actingAs($this->admin)->post(route('reports.tiers.settle'), [
            'partner_id' => $this->ami->id, 'amount' => 30000, 'paid_at' => now()->toDateString(), 'mode' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(1, PartnerSettlement::count());
        $apres = $this->actingAs($this->admin)->get(route('reports.tiers', ['period' => 'monthly']))->viewData('partenaires')->first();
        $this->assertEquals(50000, $apres->regle);
        $this->assertEquals(25000, $apres->solde);
    }

    public function test_un_vendeur_voit_ses_ventes_tierces_mais_ne_regle_pas(): void
    {
        $vendeur = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->magasin->id);
        $this->venteMixte([['product_id' => $this->robinet->id, 'quantity' => 1, 'prix' => 30000, 'purchase_price_from_partner' => 25000]], $vendeur)
            ->assertSessionHas('success');

        $page = $this->actingAs($vendeur)->get(route('reports.tiers', ['period' => 'monthly']))->assertOk();
        $page->assertSee('Robinet')->assertDontSee('Enregistrer un règlement');
        $this->assertFalse($page->viewData('peutRegler'));

        $this->actingAs($vendeur)->post(route('reports.tiers.settle'), [
            'partner_id' => $this->ami->id, 'amount' => 1000, 'paid_at' => now()->toDateString(), 'mode' => 'cash',
        ])->assertForbidden();
    }

    public function test_exports_pdf_et_excel_du_rapport(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->venteMixte([['product_id' => $this->robinet->id, 'quantity' => 3, 'prix' => 30000, 'purchase_price_from_partner' => 25000]]);
        $this->actingAs($this->admin)->post(route('reports.tiers.settle'), [
            'partner_id' => $this->ami->id, 'amount' => 50000, 'paid_at' => now()->toDateString(), 'mode' => 'cash',
        ]);

        // PDF : téléchargement, données de la page, gabarit rendu sans erreur.
        $pdf = $this->actingAs($this->admin)->get(route('reports.tiers.pdf', ['period' => 'monthly']))->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringContainsString('ventes_tierces_', $pdf->headers->get('content-disposition'));

        // Contenu du gabarit PDF, rendu avec les données de la page.
        $page = $this->actingAs($this->admin)->get(route('reports.tiers', ['period' => 'monthly']));
        $html = view('exports.ventes-tierces-pdf', $page->original->getData() + ['company' => null])->render();
        $this->assertStringContainsString('RAPPORT DES VENTES TIERCES', $html);
        $this->assertStringContainsString('Moussa Quincaillerie', $html);
        $this->assertStringContainsString('3 cartons', $html);
        $this->assertStringContainsString('25 000', $html); // reste à payer
        $this->assertStringContainsString('Document interne', $html);

        // Excel : trois feuilles, montants numériques.
        $this->actingAs($this->admin)->get(route('reports.tiers.excel', ['period' => 'monthly']))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded(
            'ventes_tierces_' . now()->startOfMonth()->format('Y-m-d') . '_au_' . now()->endOfMonth()->format('Y-m-d') . '.xlsx',
            function (\App\Exports\VentesTiercesExport $export) {
                $feuilles = $export->sheets();
                $this->assertSame(['Par partenaire', 'Détail', 'Règlements'], array_map(fn ($f) => $f->title(), $feuilles));
                $partenaire = $feuilles[0]->array()[0];
                $this->assertSame('Moussa Quincaillerie', $partenaire[0]);
                $this->assertSame('3 cartons', $partenaire[3]);
                $this->assertEquals([75000, 50000, 25000], array_slice($partenaire, 7, 3));
                $ligne = $feuilles[1]->array()[0];
                $this->assertSame([3.0, 'cartons', 25000.0, 30000.0], array_slice($ligne, 6, 4));
                $this->assertEquals(50000, $feuilles[2]->array()[0][2]);

                return true;
            }
        );
    }

    public function test_export_vendeur_sans_soldes_ni_reglements(): void
    {
        \Maatwebsite\Excel\Facades\Excel::fake();
        $vendeur = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->magasin->id);
        $this->venteMixte([['product_id' => $this->robinet->id, 'quantity' => 1, 'prix' => 30000, 'purchase_price_from_partner' => 25000]], $vendeur);

        $this->actingAs($vendeur)->get(route('reports.tiers.pdf', ['period' => 'monthly']))->assertOk();
        $this->actingAs($vendeur)->get(route('reports.tiers.excel', ['period' => 'monthly']))->assertOk();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded(
            'ventes_tierces_' . now()->startOfMonth()->format('Y-m-d') . '_au_' . now()->endOfMonth()->format('Y-m-d') . '.xlsx',
            function (\App\Exports\VentesTiercesExport $export) {
                $feuilles = $export->sheets();
                $this->assertCount(2, $feuilles); // pas de feuille « Règlements »
                $this->assertCount(7, $feuilles[0]->headings()[1]); // pas de colonnes de solde

                return true;
            }
        );
    }
}
