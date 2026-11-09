<?php

namespace Rasel673\EcommerceAI\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Registry\ToolRegistry;
use Rasel673\EcommerceAI\Tests\TestCase;
use Rasel673\EcommerceAI\Tools\LowStockTool;
use Rasel673\EcommerceAI\Tools\OrderSummaryTool;
use Rasel673\EcommerceAI\Tools\ProductStockTool;
use Rasel673\EcommerceAI\Tools\TopProductsTool;

class AnalyticsToolsTest extends TestCase
{
    #[Test]
    public function it_executes_order_summary_tool(): void
    {
        $mock = $this->createMock(EcommerceAdapterInterface::class);
        $mock->expects($this->once())
            ->method('getOrderSummary')
            ->willReturn([
                'total_orders' => 50,
                'status_breakdown' => ['completed' => 45, 'pending' => 5],
                'average_order_value' => 1250.50,
                'currency' => 'BDT',
                'period' => 'today',
            ]);

        $tool = new OrderSummaryTool($mock);
        $result = $tool->execute(['period' => 'today']);

        $this->assertEquals('get_order_summary', $tool->getName());
        $this->assertEquals(50, $result['total_orders']);
        $this->assertEquals(45, $result['status_breakdown']['completed']);
        $this->assertEquals(1250.50, $result['average_order_value']);
    }

    #[Test]
    public function it_executes_top_products_tool(): void
    {
        $mock = $this->createMock(EcommerceAdapterInterface::class);
        $mock->expects($this->once())
            ->method('getTopSellingProducts')
            ->with(3)
            ->willReturn([
                ['id' => 1, 'name' => 'Running Shoes', 'sku' => 'SHOE-01', 'total_quantity_sold' => 30, 'total_revenue' => 60000.0],
                ['id' => 2, 'name' => 'Sports Cap', 'sku' => 'CAP-02', 'total_quantity_sold' => 20, 'total_revenue' => 10000.0],
                ['id' => 3, 'name' => 'Socks Pair', 'sku' => 'SCK-03', 'total_quantity_sold' => 15, 'total_revenue' => 3000.0],
            ]);

        $tool = new TopProductsTool($mock);
        $result = $tool->execute(['limit' => 3, 'period' => 'this_month']);

        $this->assertEquals('get_top_products', $tool->getName());
        $this->assertEquals(3, $result['count']);
        $this->assertCount(3, $result['products']);
        $this->assertEquals('Running Shoes', $result['products'][0]['name']);
    }

    #[Test]
    public function it_executes_low_stock_tool(): void
    {
        $mock = $this->createMock(EcommerceAdapterInterface::class);
        $mock->expects($this->once())
            ->method('getLowStockProducts')
            ->with(5, 10)
            ->willReturn([
                ['id' => 10, 'name' => 'Gaming Mouse', 'sku' => 'MSE-01', 'current_stock' => 2, 'threshold' => 5],
            ]);

        $tool = new LowStockTool($mock);
        $result = $tool->execute(['threshold' => 5, 'limit' => 10]);

        $this->assertEquals('get_low_stock_products', $tool->getName());
        $this->assertEquals(5, $result['threshold']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals(2, $result['products'][0]['current_stock']);
    }

    #[Test]
    public function it_executes_product_stock_tool(): void
    {
        $mock = $this->createMock(EcommerceAdapterInterface::class);
        $mock->expects($this->once())
            ->method('getProductStock')
            ->with('Keyboard')
            ->willReturn([
                ['id' => 7, 'name' => 'Mechanical Keyboard RGB', 'sku' => 'KB-RGB-01', 'current_stock' => 14, 'price' => 4500.0, 'is_in_stock' => true],
            ]);

        $tool = new ProductStockTool($mock);
        $result = $tool->execute(['search' => 'Keyboard']);

        $this->assertEquals('get_product_stock', $tool->getName());
        $this->assertEquals(1, $result['found_count']);
        $this->assertTrue($result['products'][0]['is_in_stock']);
    }

    #[Test]
    public function it_validates_product_stock_search_term(): void
    {
        $mock = $this->createMock(EcommerceAdapterInterface::class);
        $tool = new ProductStockTool($mock);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Search term cannot be empty.');

        $tool->execute(['search' => '   ']);
    }

    #[Test]
    public function it_registers_all_five_tools_in_service_provider(): void
    {
        $registry = $this->app->make(ToolRegistry::class);

        $this->assertTrue($registry->has('get_sales_summary'));
        $this->assertTrue($registry->has('get_order_summary'));
        $this->assertTrue($registry->has('get_top_products'));
        $this->assertTrue($registry->has('get_low_stock_products'));
        $this->assertTrue($registry->has('get_product_stock'));

        $openAiTools = $registry->toOpenAITools();
        $this->assertCount(5, $openAiTools);
    }
}

