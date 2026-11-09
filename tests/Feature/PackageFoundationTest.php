<?php

namespace Rasel673\EcommerceAI\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Tests\TestCase;

class PackageFoundationTest extends TestCase
{
    #[Test]
    public function it_loads_the_package_configuration(): void
    {
        $this->assertTrue(config('ecommerce-ai.enabled'));
        $this->assertEquals('test-key', config('ecommerce-ai.openai.api_key'));
        $this->assertEquals('gpt-4o-mini', config('ecommerce-ai.openai.model'));
        $this->assertEquals('api/ecommerce-ai', config('ecommerce-ai.route.prefix'));
    }

    #[Test]
    public function it_loads_the_package_views(): void
    {
        $this->assertTrue(view()->exists('ecommerce-ai::chatbot'));
    }
}


