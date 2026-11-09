<?php

namespace Rasel673\EcommerceAI\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Contracts\ToolInterface;
use Rasel673\EcommerceAI\Tests\TestCase;
use Rasel673\EcommerceAI\Tools\SalesTool;

class SalesToolTest extends TestCase
{
    #[Test]
    public function it_implements_tool_interface_with_valid_schema(): void
    {
        $mockAdapter = $this->createMock(EcommerceAdapterInterface::class);
        $tool = new SalesTool($mockAdapter);

        $this->assertInstanceOf(ToolInterface::class, $tool);
        $this->assertEquals('get_sales_summary', $tool->getName());
        $this->assertNotEmpty($tool->getDescription());

        $schema = $tool->getParametersSchema();
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('period', $schema['properties']);
        $this->assertArrayHasKey('start_date', $schema['properties']);
        $this->assertArrayHasKey('end_date', $schema['properties']);
    }

    #[Test]
    public function it_executes_sales_summary_with_default_period(): void
    {
        $mockAdapter = $this->createMock(EcommerceAdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('getSalesSummary')
            ->with($this->callback(function ($filters) {
                return $filters['period'] === 'today'
                    && $filters['start_date'] === null
                    && $filters['end_date'] === null;
            }))
            ->willReturn([
                'period' => 'today',
                'total_orders' => 25,
                'gross_sales' => 45000.0,
                'currency' => 'BDT',
                'start_date' => '2026-11-09 00:00:00',
                'end_date' => '2026-11-09 23:59:59',
            ]);

        $tool = new SalesTool($mockAdapter);
        $result = $tool->execute();

        $this->assertEquals('today', $result['period']);
        $this->assertEquals(25, $result['total_orders']);
        $this->assertEquals(45000.0, $result['gross_sales']);
        $this->assertEquals('BDT', $result['currency']);
    }

    #[Test]
    public function it_executes_sales_summary_for_custom_range(): void
    {
        $mockAdapter = $this->createMock(EcommerceAdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('getSalesSummary')
            ->with($this->callback(function ($filters) {
                return $filters['period'] === 'custom'
                    && $filters['start_date'] === '2026-11-01'
                    && $filters['end_date'] === '2026-11-05';
            }))
            ->willReturn([
                'period' => 'custom',
                'total_orders' => 120,
                'gross_sales' => 180000.0,
                'currency' => 'BDT',
                'start_date' => '2026-11-01 00:00:00',
                'end_date' => '2026-11-05 23:59:59',
            ]);

        $tool = new SalesTool($mockAdapter);
        $result = $tool->execute([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
        ]);

        $this->assertEquals('custom', $result['period']);
        $this->assertEquals(120, $result['total_orders']);
        $this->assertEquals(180000.0, $result['gross_sales']);
    }

    #[Test]
    public function it_validates_date_format(): void
    {
        $mockAdapter = $this->createMock(EcommerceAdapterInterface::class);
        $tool = new SalesTool($mockAdapter);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid start_date format. Must be YYYY-MM-DD.');

        $tool->execute(['start_date' => '01-11-2026']);
    }

    #[Test]
    public function it_rejects_start_date_greater_than_end_date(): void
    {
        $mockAdapter = $this->createMock(EcommerceAdapterInterface::class);
        $tool = new SalesTool($mockAdapter);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('start_date must be before or equal to end_date.');

        $tool->execute([
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-05',
        ]);
    }

    #[Test]
    public function it_enforces_store_tenant_context(): void
    {
        $mockAdapter = $this->createMock(EcommerceAdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('getSalesSummary')
            ->with($this->callback(function ($filters) {
                return $filters['store_id'] === 99
                    && $filters['period'] === 'this_month';
            }))
            ->willReturn([
                'period' => 'this_month',
                'total_orders' => 10,
                'gross_sales' => 15000.0,
                'currency' => 'BDT',
            ]);

        $tool = new SalesTool($mockAdapter);
        $result = $tool->execute(
            arguments: ['period' => 'this_month'],
            context: ['store_id' => 99]
        );

        $this->assertEquals(10, $result['total_orders']);
    }
}

