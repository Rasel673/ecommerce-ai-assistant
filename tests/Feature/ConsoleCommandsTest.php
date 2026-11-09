<?php

namespace Rasel673\EcommerceAI\Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up generated adapters in app_path('Adapters')
        if (File::isDirectory(app_path('Adapters'))) {
            File::deleteDirectory(app_path('Adapters'));
        }

        parent::tearDown();
    }

    #[Test]
    public function it_generates_custom_ecommerce_adapter_via_command(): void
    {
        $this->artisan('make:ecommerce-adapter', ['name' => 'CustomShopAdapter'])
            ->expectsOutputToContain('Ecommerce Adapter [CustomShopAdapter] created successfully')
            ->assertExitCode(0);

        $targetFile = app_path('Adapters/CustomShopAdapter.php');
        $this->assertFileExists($targetFile);

        $content = File::get($targetFile);
        $this->assertStringContainsString('namespace App\Adapters;', $content);
        $this->assertStringContainsString('class CustomShopAdapter implements EcommerceAdapterInterface', $content);
        $this->assertStringContainsString('public function getSalesSummary', $content);
        $this->assertStringContainsString('public function getOrderSummary', $content);
        $this->assertStringContainsString('public function getTopSellingProducts', $content);
        $this->assertStringContainsString('public function getLowStockProducts', $content);
        $this->assertStringContainsString('public function getProductStock', $content);
    }

    #[Test]
    public function it_runs_package_install_command_and_generates_adapter(): void
    {
        $this->artisan('ecommerce-ai:install', ['--adapter' => 'MultipointEcommerceAdapter'])
            ->expectsOutputToContain('Installing Ecommerce AI Assistant')
            ->expectsOutputToContain('Ecommerce Adapter [MultipointEcommerceAdapter] created successfully')
            ->assertExitCode(0);

        $targetFile = app_path('Adapters/MultipointEcommerceAdapter.php');
        $this->assertFileExists($targetFile);

        $content = File::get($targetFile);
        $this->assertStringContainsString('class MultipointEcommerceAdapter implements EcommerceAdapterInterface', $content);
    }
}

