<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Customer;
use App\Models\Facture;
use App\Models\Payment;
use App\Models\TransactionFacture;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        DB::purge('sqlite');

        Schema::create('customers', function ($table) {
            $table->id();
            $table->string('mark')->nullable();
            $table->string('customerName')->nullable();
            $table->string('tel')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->tinyInteger('fidelite')->default(1);
            $table->decimal('total_taken', 15, 2)->default(0);
            $table->decimal('total_repaid', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('factures', function ($table) {
            $table->id();
            $table->string('numero_facture')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('montant_total', 15, 2)->nullable();
            $table->decimal('avance', 15, 2)->nullable();
            $table->decimal('reste', 15, 2)->nullable();
            $table->string('statut')->default('non payé');
            $table->string('livraison')->default('non livré');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('facture_id');
            $table->unsignedBigInteger('transaction_facture_id')->nullable();
            $table->string('receipt_number')->nullable()->unique();
            $table->decimal('versement', 15, 2);
            $table->decimal('total_paye', 15, 2);
            $table->decimal('reste', 15, 2);
            $table->string('paid_by')->default('cash');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_factures', function ($table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('receipt_number')->nullable()->unique();
            $table->string('paid_by')->nullable();
            $table->decimal('versement', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->decimal('balance_apres', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Rôle admin : évite la branche de scoping par boutique (role_id == 3), qui
        // n'est pas ce que ce test couvre.
        $admin = new User();
        $admin->forceFill(['id' => 1, 'role_id' => User::ROLE_ADMIN]);
        $admin->exists = true;
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('transaction_factures');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('factures');
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    private function makeCustomerWithInvoices(): Customer
    {
        $customer = Customer::create([
            'mark' => 'AMD-TEST',
            'customerName' => 'Client Test',
            'tel' => '000',
            'address' => 'Adresse',
            'fidelite' => 1,
            'balance' => 15000,
        ]);

        Facture::create([
            'numero_facture' => 'FAC-1',
            'customer_id' => $customer->id,
            'montant_total' => 10000,
            'avance' => 0,
            'reste' => 10000,
            'statut' => 'non payé',
        ]);

        Facture::create([
            'numero_facture' => 'FAC-2',
            'customer_id' => $customer->id,
            'montant_total' => 5000,
            'avance' => 0,
            'reste' => 5000,
            'statut' => 'non payé',
        ]);

        return $customer;
    }

    public function test_store_payment_splits_across_invoices_and_updates_customer_balance(): void
    {
        $customer = $this->makeCustomerWithInvoices();

        $request = Request::create('/payments/store', 'POST', [
            'customer_id' => $customer->id,
            'paid_by' => 'cash',
            'versement' => '12000',
            'note' => 'Test',
        ]);

        app(PaymentController::class)->storePayment($request);

        $invoice1 = Facture::where('numero_facture', 'FAC-1')->first();
        $invoice2 = Facture::where('numero_facture', 'FAC-2')->first();

        $this->assertSame('payé', $invoice1->statut);
        $this->assertEquals(0, $invoice1->reste);
        $this->assertSame('non payé', $invoice2->statut);
        $this->assertEquals(3000, $invoice2->reste); // 5000 - (12000 - 10000 déjà appliqués à FAC-1)

        $this->assertEquals(2, Payment::count());
        $this->assertEquals(1, TransactionFacture::count());
        // Reçu de transaction : solde avant et après, paiements rattachés.
        $transaction = TransactionFacture::first();
        $this->assertEquals(15000, $transaction->solde_avant);
        $this->assertEquals(3000, $transaction->solde_apres);
        $this->assertEquals(2, Payment::where('transaction_facture_id', $transaction->id)->count());

        $customer->refresh();
        $this->assertEquals(3000, (float) $customer->balance); // 15000 - 12000
        $this->assertEquals(12000, (float) $customer->total_repaid);
    }

    public function test_store_payment_rolls_back_entirely_on_mid_loop_failure(): void
    {
        $customer = $this->makeCustomerWithInvoices();

        // Provoque une violation de contrainte unique sur le 2e paiement de la boucle
        // (même numéro de reçu), pour vérifier que DB::transaction() annule bien TOUT —
        // y compris le paiement de la facture 1, déjà inséré avant l'échec — au lieu de
        // laisser la base dans un état à moitié cohérent (régression testée : Fix #9).
        $nextReceiptId = (Payment::max('id') ?? 0) + 1;
        $collidingReceipt = generateReceiptNumber('RCF', $nextReceiptId + 1);

        Payment::create([
            'facture_id' => Facture::where('numero_facture', 'FAC-1')->value('id'),
            'receipt_number' => $collidingReceipt,
            'versement' => 1,
            'total_paye' => 1,
            'reste' => 1,
            'paid_by' => 'cash',
            'note' => 'Collision préparée par le test',
        ]);

        $request = Request::create('/payments/store', 'POST', [
            'customer_id' => $customer->id,
            'paid_by' => 'cash',
            'versement' => '12000',
            'note' => 'Test',
        ]);

        app(PaymentController::class)->storePayment($request);

        // La facture 1 ne doit PAS avoir été soldée : la transaction a tout annulé.
        $invoice1 = Facture::where('numero_facture', 'FAC-1')->first();
        $this->assertSame('non payé', $invoice1->statut);
        $this->assertEquals(10000, $invoice1->reste);

        // Seul le paiement de collision inséré par le test doit exister.
        $this->assertEquals(1, Payment::count());
        $this->assertEquals(0, TransactionFacture::count());

        $customer->refresh();
        $this->assertEquals(15000, (float) $customer->balance);
    }

    public function test_store_single_payment_marks_invoice_paid_and_updates_balance(): void
    {
        $customer = $this->makeCustomerWithInvoices();
        $facture = Facture::where('numero_facture', 'FAC-1')->first();

        $request = Request::create('/payments/store', 'POST', [
            'facture_id' => $facture->id,
            'paid_by' => 'cash',
            'versement' => '10000',
            'note' => 'Solde',
        ]);

        app(PaymentController::class)->store($request);

        $facture->refresh();
        $this->assertSame('payé', $facture->statut);
        $this->assertEquals(0, $facture->reste);

        $customer->refresh();
        $this->assertEquals(5000, (float) $customer->balance); // 15000 - 10000
    }

    public function test_store_single_payment_rejects_overpayment(): void
    {
        $customer = $this->makeCustomerWithInvoices();
        $facture = Facture::where('numero_facture', 'FAC-1')->first();

        $request = Request::create('/payments/store', 'POST', [
            'facture_id' => $facture->id,
            'paid_by' => 'cash',
            'versement' => '999999',
            'note' => 'Trop',
        ]);

        app(PaymentController::class)->store($request);

        $this->assertEquals(0, Payment::count());
        $facture->refresh();
        $this->assertEquals(10000, $facture->reste);
    }
}
