<?php

namespace Rasel673\EcommerceAI\Providers;

use Rasel673\EcommerceAI\Exceptions\AIProviderException;

class MistralProvider extends OpenAIProvider
{
    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeout = null,
        ?float $temperature = null,
        ?string $baseUrl = null
    ) {
        parent::__construct(
            apiKey: $apiKey ?? config('ecommerce-ai.providers.mistral.api_key', config('ecommerce-ai.mistral.api_key')),
            model: $model ?? config('ecommerce-ai.providers.mistral.model', config('ecommerce-ai.mistral.model', 'mistral-small-latest')),
            timeout: $timeout ?? (int) config('ecommerce-ai.providers.mistral.timeout', 30),
            temperature: $temperature ?? config('ecommerce-ai.providers.mistral.temperature', 0.7),
            baseUrl: $baseUrl ?? config('ecommerce-ai.providers.mistral.base_url', 'https://api.mistral.ai/v1')
        );
    }

    protected function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new AIProviderException('Mistral API key is missing. Set MISTRAL_API_KEY in your .env.');
        }
    }
}
