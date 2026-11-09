<?php

namespace Rasel673\EcommerceAI\Providers;

class OllamaProvider extends OpenAIProvider
{
    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeout = null,
        ?float $temperature = null,
        ?string $baseUrl = null
    ) {
        parent::__construct(
            apiKey: $apiKey ?? config('ecommerce-ai.providers.ollama.api_key', 'ollama'),
            model: $model ?? config('ecommerce-ai.providers.ollama.model', config('ecommerce-ai.ollama.model', 'llama3.2')),
            timeout: $timeout ?? (int) config('ecommerce-ai.providers.ollama.timeout', 60),
            temperature: $temperature ?? config('ecommerce-ai.providers.ollama.temperature', 0.7),
            baseUrl: $baseUrl ?? config('ecommerce-ai.providers.ollama.base_url', 'http://localhost:11434/v1')
        );
    }

    /**
     * Ollama runs locally and does not strictly require an API key.
     */
    protected function ensureApiKeyConfigured(): void
    {
        // No-op for local Ollama instances
        if (empty($this->apiKey)) {
            $this->apiKey = 'ollama';
        }
    }
}
