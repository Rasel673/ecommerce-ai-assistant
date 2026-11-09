<?php

namespace Rasel673\EcommerceAI;

use Illuminate\Support\ServiceProvider;
use Rasel673\EcommerceAI\Adapters\GenericEcommerceAdapter;
use Rasel673\EcommerceAI\Agent\AgentRunner;
use Rasel673\EcommerceAI\Contracts\AIProviderInterface;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Providers\AIProviderFactory;
use Rasel673\EcommerceAI\Providers\OpenAIProvider;
use Rasel673\EcommerceAI\Registry\ToolRegistry;
use Rasel673\EcommerceAI\Tools\LowStockTool;
use Rasel673\EcommerceAI\Tools\OrderSummaryTool;
use Rasel673\EcommerceAI\Tools\ProductStockTool;
use Rasel673\EcommerceAI\Tools\SalesTool;
use Rasel673\EcommerceAI\Tools\TopProductsTool;

class EcommerceAIServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration with host application
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ecommerce-ai.php',
            'ecommerce-ai'
        );

        // Bind default AI Provider interface via AIProviderFactory
        $this->app->singleton(AIProviderInterface::class, function () {
            return AIProviderFactory::make();
        });

        // Bind default Ecommerce Adapter interface
        $this->app->singleton(EcommerceAdapterInterface::class, function ($app) {
            $adapterClass = config('ecommerce-ai.adapter', GenericEcommerceAdapter::class);

            if (empty($adapterClass) || !class_exists($adapterClass)) {
                return new GenericEcommerceAdapter();
            }

            return $app->make($adapterClass);
        });

        // Bind Tool Registry and register all 5 analytics tools
        $this->app->singleton(ToolRegistry::class, function ($app) {
            $registry = new ToolRegistry();
            $adapter = $app->make(EcommerceAdapterInterface::class);

            $registry->register(new SalesTool($adapter))
                ->register(new OrderSummaryTool($adapter))
                ->register(new TopProductsTool($adapter))
                ->register(new LowStockTool($adapter))
                ->register(new ProductStockTool($adapter));

            return $registry;
        });

        // Bind Agent Runner
        $this->app->singleton(AgentRunner::class, function ($app) {
            return new AgentRunner(
                $app->make(AIProviderInterface::class),
                $app->make(ToolRegistry::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutes();

        // Load package views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ecommerce-ai');

        // Publishing assets when running in console
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/ecommerce-ai.php' => config_path('ecommerce-ai.php'),
            ], 'ecommerce-ai-config');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/ecommerce-ai'),
            ], 'ecommerce-ai-views');

            // Register artisan console commands
            $this->commands([
                \Rasel673\EcommerceAI\Console\Commands\MakeEcommerceAdapterCommand::class,
                \Rasel673\EcommerceAI\Console\Commands\InstallEcommerceAICommand::class,
            ]);
        }
    }

    /**
     * Load package routes using configured prefix and middleware.
     */
    protected function loadRoutes(): void
    {
        $routesPath = __DIR__ . '/../routes/api.php';

        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
    }
}

