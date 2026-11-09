<?php

namespace Rasel673\EcommerceAI\Providers;

use Rasel673\EcommerceAI\Exceptions\AIProviderException;

class DeepSeekProvider extends OpenAIProvider
{
    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeout = null,
        ?float $temperature = null,
        ?string $baseUrl = null
    ) {
        parent::__construct(
            apiKey: $apiKey ?? config('ecommerce-ai.providers.deepseek.api_key', config('ecommerce-ai.deepseek.api_key')),
            model: $model ?? config('ecommerce-ai.providers.deepseek.model', config('ecommerce-ai.deepseek.model', 'deepseek-chat')),
            timeout: $timeout ?? (int) config('ecommerce-ai.providers.deepseek.timeout', 30),
            temperature: $temperature ?? config('ecommerce-ai.providers.deepseek.temperature', 0.7),
            baseUrl: $baseUrl ?? config('ecommerce-ai.providers.deepseek.base_url', 'https://api.deepseek.com/v1')
        );
    }

    protected function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new AIProviderException('DeepSeek API key is missing. Set DEEPSEEK_API_KEY in your .env.');
        }
    }
}
