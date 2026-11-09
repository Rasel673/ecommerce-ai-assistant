<?php

namespace Rasel673\EcommerceAI\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Rasel673\EcommerceAI\EcommerceAIServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            EcommerceAIServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default configuration for testing
        $app['config']->set('ecommerce-ai.enabled', true);
        $app['config']->set('ecommerce-ai.openai.api_key', 'test-key');
        $app['config']->set('ecommerce-ai.openai.model', 'gpt-4o-mini');
        $app['config']->set('ecommerce-ai.providers.anthropic.base_url', 'https://api.anthropic.com/v1');
        $app['config']->set('ecommerce-ai.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }
}

