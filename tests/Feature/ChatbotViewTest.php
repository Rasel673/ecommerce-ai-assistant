<?php

namespace Rasel673\EcommerceAI\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Tests\TestCase;

class ChatbotViewTest extends TestCase
{
    #[Test]
    public function it_renders_chatbot_widget_view_when_enabled(): void
    {
        $rendered = view('ecommerce-ai::chatbot')->render();

        $this->assertStringContainsString('id="ecommerce-ai-wrapper"', $rendered);
        $this->assertStringContainsString('id="ecommerce-ai-launcher"', $rendered);
        $this->assertStringContainsString('id="ecommerce-ai-window"', $rendered);
        $this->assertStringContainsString('#7c3aed', $rendered);
        $this->assertStringContainsString('/api/ecommerce-ai/chat', $rendered);
    }

    #[Test]
    public function it_does_not_render_chatbot_widget_when_disabled(): void
    {
        $this->app['config']->set('ecommerce-ai.ui.enabled', false);

        $rendered = view('ecommerce-ai::chatbot')->render();

        $this->assertStringNotContainsString('id="ecommerce-ai-wrapper"', $rendered);
    }
}

