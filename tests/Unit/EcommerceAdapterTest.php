<?php

namespace Rasel673\EcommerceAI\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Adapters\GenericEcommerceAdapter;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Support\DateRangeResolver;
use Rasel673\EcommerceAI\Tests\TestCase;

class EcommerceAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabaseSchema();
    }

    protected function setUpDatabaseSchema(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->default(1);
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('completed');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->default(1);
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->default(0.0);
            $table->integer('stock')->default(0);
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->default(1);
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('qty')->default(1);
            $table->decimal('price', 10, 2)->default(0.0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('products');
        Schema::dropIfExists('orders');
        parent::tearDown();
    }

    #[Test]
    public function it_resolves_ecommerce_adapter_from_container(): void
    {
        $adapter = $this->app->make(EcommerceAdapterInterface::class);

        $this->assertInstanceOf(GenericEcommerceAdapter::class, $adapter);
    }

    #[Test]
    public function it_resolves_date_ranges_correctly(): void
    {
        $today = DateRangeResolver::resolve(['period' => 'today']);
        $this->assertEquals('today', $today['period']);
        $this->assertEquals(Carbon::today()->startOfDay()->toDateTimeString(), $today['start_string']);
        $this->assertEquals(Carbon::today()->endOfDay()->toDateTimeString(), $today['end_string']);

        $custom = DateRangeResolver::resolve([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
        ]);
        $this->assertEquals('custom', $custom['period']);
        $this->assertEquals('2026-01-01 00:00:00', $custom['start_string']);
        $this->assertEquals('2026-01-15 23:59:59', $custom['end_string']);
    }

    #[Test]
    public function it_calculates_sales_summary(): void
    {
        DB::table('orders')->insert([
            ['store_id' => 1, 'total_amount' => 1500.00, 'status' => 'completed', 'created_at' => Carbon::now()],
            ['store_id' => 1, 'total_amount' => 2500.00, 'status' => 'completed', 'created_at' => Carbon::now()],
            ['store_id' => 2, 'total_amount' => 5000.00, 'status' => 'completed', 'created_at' => Carbon::now()], // Different store
        ]);

        $adapter = new GenericEcommerceAdapter();

        // Scope to store 1
        $summary = $adapter->getSalesSummary(['period' => 'today', 'store_id' => 1]);

        $this->assertEquals('today', $summary['period']);
        $this->assertEquals(2, $summary['total_orders']);
        $this->assertEquals(4000.00, $summary['gross_sales']);
        $this->assertEquals('BDT', $summary['currency']);
    }

    #[Test]
    public function it_calculates_order_summary_and_status_breakdown(): void
    {
        DB::table('orders')->insert([
            ['store_id' => 1, 'total_amount' => 1000.00, 'status' => 'completed', 'created_at' => Carbon::now()],
            ['store_id' => 1, 'total_amount' => 2000.00, 'status' => 'pending', 'created_at' => Carbon::now()],
            ['store_id' => 1, 'total_amount' => 3000.00, 'status' => 'completed', 'created_at' => Carbon::now()],
        ]);

        $adapter = new GenericEcommerceAdapter();
        $summary = $adapter->getOrderSummary(['period' => 'today']);

        $this->assertEquals(3, $summary['total_orders']);
        $this->assertEquals(2000.00, $summary['average_order_value']);
        $this->assertEquals(2, $summary['status_breakdown']['completed']);
        $this->assertEquals(1, $summary['status_breakdown']['pending']);
    }

    #[Test]
    public function it_retrieves_top_selling_products(): void
    {
        $p1 = DB::table('products')->insertGetId(['name' => 'Cotton T-Shirt', 'sku' => 'TSHIRT-01', 'created_at' => Carbon::now()]);
        $p2 = DB::table('products')->insertGetId(['name' => 'Denim Jeans', 'sku' => 'JEANS-01', 'created_at' => Carbon::now()]);

        DB::table('order_items')->insert([
            ['order_id' => 1, 'product_id' => $p1, 'qty' => 10, 'price' => 500.00, 'created_at' => Carbon::now()],
            ['order_id' => 2, 'product_id' => $p2, 'qty' => 4, 'price' => 1200.00, 'created_at' => Carbon::now()],
        ]);

        $adapter = new GenericEcommerceAdapter();
        $top = $adapter->getTopSellingProducts(5, ['period' => 'today']);

        $this->assertCount(2, $top);
        $this->assertEquals('Cotton T-Shirt', $top[0]['name']);
        $this->assertEquals(10, $top[0]['total_quantity_sold']);
        $this->assertEquals(5000.00, $top[0]['total_revenue']);
    }

    #[Test]
    public function it_retrieves_low_stock_products(): void
    {
        DB::table('products')->insert([
            ['name' => 'Leather Wallet', 'sku' => 'WLT-01', 'stock' => 3, 'created_at' => Carbon::now()],
            ['name' => 'Canvas Backpack', 'sku' => 'BPK-01', 'stock' => 25, 'created_at' => Carbon::now()],
            ['name' => 'Sunglasses', 'sku' => 'SGL-01', 'stock' => 5, 'created_at' => Carbon::now()],
        ]);

        $adapter = new GenericEcommerceAdapter();
        $lowStock = $adapter->getLowStockProducts(threshold: 5, limit: 10);

        $this->assertCount(2, $lowStock);
        $this->assertEquals('Leather Wallet', $lowStock[0]['name']);
        $this->assertEquals(3, $lowStock[0]['current_stock']);
    }

    #[Test]
    public function it_searches_product_stock_by_name_or_sku(): void
    {
        DB::table('products')->insert([
            ['name' => 'Wireless Bluetooth Earbuds', 'sku' => 'EAR-BT-01', 'price' => 2200.00, 'stock' => 12, 'created_at' => Carbon::now()],
            ['name' => 'Wired Gaming Mouse', 'sku' => 'MSE-G-02', 'price' => 1500.00, 'stock' => 0, 'created_at' => Carbon::now()],
        ]);

        $adapter = new GenericEcommerceAdapter();

        $searchByName = $adapter->getProductStock('Earbuds');
        $this->assertCount(1, $searchByName);
        $this->assertEquals('Wireless Bluetooth Earbuds', $searchByName[0]['name']);
        $this->assertTrue($searchByName[0]['is_in_stock']);

        $searchBySku = $adapter->getProductStock('MSE-G');
        $this->assertCount(1, $searchBySku);
        $this->assertEquals(0, $searchBySku[0]['current_stock']);
        $this->assertFalse($searchBySku[0]['is_in_stock']);
    }
}

