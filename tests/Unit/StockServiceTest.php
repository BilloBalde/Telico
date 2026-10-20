<?php

namespace Tests\Unit;

use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockServiceTest extends TestCase
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

        Schema::create('products', function ($table) {
            $table->id();
            $table->integer('pcs')->default(0);
            $table->string('unite', 20)->default('pcs');
            $table->timestamps();
        });

        Schema::create('store_products', function ($table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->unsignedBigInteger('fund_id')->nullable();
            $table->timestamps();
        });

        // Journal des mouvements, écrit par StockService à chaque variation.
        Schema::create('stock_movements', function ($table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->string('type', 30);
            $table->integer('quantity');
            $table->string('unite', 20)->nullable();
            $table->integer('stock_before')->nullable();
            $table->integer('stock_after')->nullable();
            $table->unsignedBigInteger('counterpart_store_id')->nullable();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('document_ref')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('fund_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('store_products');
        Schema::dropIfExists('products');

        parent::tearDown();
    }

    public function test_decrement_if_available_updates_store_and_global_stock(): void
    {
        DB::table('products')->insert(['id' => 1, 'pcs' => 10]);
        DB::table('store_products')->insert([
            'id' => 1,
            'store_id' => 7,
            'product_id' => 1,
            'quantity' => 10,
        ]);

        $service = new StockService();

        $this->assertTrue($service->decrementIfAvailable(7, 1, 3));
        $this->assertSame(7, DB::table('store_products')->where('product_id', 1)->where('store_id', 7)->value('quantity'));
        $this->assertSame(7, DB::table('products')->where('id', 1)->value('pcs'));

        $mouvement = DB::table('stock_movements')->first();
        $this->assertSame(-3, (int) $mouvement->quantity);
        $this->assertSame(10, (int) $mouvement->stock_before);
        $this->assertSame(7, (int) $mouvement->stock_after);
    }

    public function test_decrement_if_available_refuses_and_leaves_stock_untouched_when_insufficient(): void
    {
        DB::table('products')->insert(['id' => 1, 'pcs' => 5]);
        DB::table('store_products')->insert([
            'id' => 1,
            'store_id' => 7,
            'product_id' => 1,
            'quantity' => 5,
        ]);

        $service = new StockService();

        $this->assertFalse($service->decrementIfAvailable(7, 1, 6));
        // Aucune écriture ne doit avoir eu lieu, ni au niveau boutique ni au global.
        $this->assertSame(5, DB::table('store_products')->where('product_id', 1)->where('store_id', 7)->value('quantity'));
        $this->assertSame(5, DB::table('products')->where('id', 1)->value('pcs'));
        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    public function test_increment_creates_row_when_missing_and_updates_global_stock(): void
    {
        DB::table('products')->insert(['id' => 1, 'pcs' => 2]);

        $service = new StockService();

        // Aucune ligne store_products pour (store 9, product 1) au départ.
        $service->increment(9, 1, 4);

        $this->assertSame(4, DB::table('store_products')->where('product_id', 1)->where('store_id', 9)->value('quantity'));
        $this->assertSame(6, DB::table('products')->where('id', 1)->value('pcs'));
    }
}
