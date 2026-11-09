<?php

namespace Rasel673\EcommerceAI\Providers;

use Rasel673\EcommerceAI\Exceptions\AIProviderException;

class GroqProvider extends OpenAIProvider
{
    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeout = null,
        ?float $temperature = null,
        ?string $baseUrl = null
    ) {
        parent::__construct(
            apiKey: $apiKey ?? config('ecommerce-ai.providers.groq.api_key', config('ecommerce-ai.groq.api_key')),
            model: $model ?? config('ecommerce-ai.providers.groq.model', config('ecommerce-ai.groq.model', 'openai/gpt-oss-120b')),
            timeout: $timeout ?? (int) config('ecommerce-ai.providers.groq.timeout', 30),
            temperature: $temperature ?? config('ecommerce-ai.providers.groq.temperature', 0.7),
            baseUrl: $baseUrl ?? config('ecommerce-ai.providers.groq.base_url', 'https://api.groq.com/openai/v1')
        );
    }

    protected function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new AIProviderException('Groq API key is missing. Set GROQ_API_KEY in your .env.');
        }
    }
}

