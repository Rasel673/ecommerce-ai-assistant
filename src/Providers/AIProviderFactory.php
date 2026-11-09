<?php

namespace Rasel673\EcommerceAI\Providers;

use InvalidArgumentException;
use Rasel673\EcommerceAI\Contracts\AIProviderInterface;

class AIProviderFactory
{
    /**
     * Create an AI provider instance based on driver name or class.
     *
     * @param string|null $driver
     * @return AIProviderInterface
     */
    public static function make(?string $driver = null): AIProviderInterface
    {
        $driver = strtolower($driver ?? config('ecommerce-ai.provider', 'gemini'));

        switch ($driver) {
            case 'openai':
                return new OpenAIProvider();

            case 'anthropic':
            case 'claude':
                return new AnthropicProvider();

            case 'gemini':
            case 'google':
                return new GeminiProvider();

            case 'groq':
                return new GroqProvider();

            case 'deepseek':
                return new DeepSeekProvider();

            case 'ollama':
                return new OllamaProvider();

            case 'mistral':
                return new MistralProvider();

            default:
                if (class_exists($driver) && is_subclass_of($driver, AIProviderInterface::class)) {
                    return app($driver);
                }

                throw new InvalidArgumentException("Unsupported AI provider [{$driver}]. Supported: openai, anthropic, gemini, groq, deepseek, ollama, mistral.");
        }
    }
}

