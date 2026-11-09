<?php

namespace Rasel673\EcommerceAI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeEcommerceAdapterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:ecommerce-adapter {name=AppEcommerceAdapter : The name of the adapter class} {--force : Overwrite existing adapter file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new custom ecommerce data adapter class';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = trim($this->argument('name'));
        $className = class_basename($name);

        $targetDir = app_path('Adapters');
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $className . '.php';

        if ($this->files->exists($targetPath) && !$this->option('force')) {
            $this->error("Adapter [{$className}] already exists at {$targetPath}!");
            $this->line("Use --force to overwrite.");
            return self::FAILURE;
        }

        if (!$this->files->isDirectory($targetDir)) {
            $this->files->makeDirectory($targetDir, 0755, true);
        }

        $stub = $this->getStub();
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            ['App\\Adapters', $className],
            $stub
        );

        $this->files->put($targetPath, $content);

        $this->info("Ecommerce Adapter [{$className}] created successfully at app/Adapters/{$className}.php");
        $this->comment("Next step: Set ECOMMERCE_AI_ADAPTER=\"App\\Adapters\\{$className}\" in your .env or config/ecommerce-ai.php");

        return self::SUCCESS;
    }

    /**
     * Load stub template.
     */
    protected function getStub(): string
    {
        $stubPath = __DIR__ . '/../../../stubs/ecommerce-adapter.stub';

        return $this->files->get($stubPath);
    }
}

