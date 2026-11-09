<?php

namespace Rasel673\EcommerceAI\Console\Commands;

use Illuminate\Console\Command;

class InstallEcommerceAICommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecommerce-ai:install {--adapter=AppEcommerceAdapter : Name of custom adapter to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Ecommerce AI Assistant, publishing configs and generating adapter';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Ecommerce AI Assistant...');

        // 1. Publish configuration
        $this->comment('Publishing package configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'ecommerce-ai-config',
            '--force' => true,
        ]);

        // 2. Generate custom adapter class
        $adapterName = $this->option('adapter') ?: 'AppEcommerceAdapter';
        $this->comment("Generating custom ecommerce adapter [{$adapterName}]...");
        $this->call('make:ecommerce-adapter', [
            'name' => $adapterName,
        ]);

        $this->newLine();
        $this->info('Ecommerce AI Assistant installed successfully!');
        $this->line('1. Set OPENAI_API_KEY in your .env file.');
        $this->line("2. Verify your database mappings in app/Adapters/{$adapterName}.php.");
        $this->line("3. Configure ECOMMERCE_AI_ADAPTER=\"App\\Adapters\\{$adapterName}\" in .env.");

        return self::SUCCESS;
    }
}

