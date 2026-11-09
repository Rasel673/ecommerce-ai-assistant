<?php

namespace Rasel673\EcommerceAI\Providers;

use Illuminate\Support\Facades\Http;
use Rasel673\EcommerceAI\Contracts\AIProviderInterface;
use Rasel673\EcommerceAI\Exceptions\AIProviderException;
use Throwable;

class OpenAIProvider implements AIProviderInterface
{
    protected ?string $apiKey;
    protected string $model;
    protected int $timeout;
    protected ?float $temperature;
    protected string $baseUrl;

    /**
     * Create a new OpenAI provider instance.
     *
     * @param string|null $apiKey
     * @param string|null $model
     * @param int|null $timeout
     * @param float|null $temperature
     * @param string|null $baseUrl
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeout = null,
        ?float $temperature = null,
        ?string $baseUrl = null
    ) {
        $this->apiKey = $apiKey ?? config('ecommerce-ai.openai.api_key');
        $this->model = $model ?? config('ecommerce-ai.openai.model', 'gpt-4o-mini');
        $this->timeout = $timeout ?? (int) config('ecommerce-ai.openai.timeout', 30);
        $this->temperature = $temperature ?? config('ecommerce-ai.openai.temperature', 0.7);
        $this->baseUrl = rtrim($baseUrl ?? config('ecommerce-ai.openai.base_url', 'https://api.openai.com/v1'), '/');
    }

    /**
     * Send a chat completion request to OpenAI.
     *
     * @param array<int, array{role: string, content: string|null}> $messages
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     *
     * @throws AIProviderException
     */
    public function chat(array $messages, array $options = []): array
    {
        $this->ensureApiKeyConfigured();
        $this->validateMessages($messages);

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
        ];

        $temperature = $options['temperature'] ?? $this->temperature;
        if ($temperature !== null) {
            $payload['temperature'] = (float) $temperature;
        }

        // Support optional tool calling payloads when provided
        if (!empty($options['tools'])) {
            $payload['tools'] = $options['tools'];
            if (!empty($options['tool_choice'])) {
                $payload['tool_choice'] = $options['tool_choice'];
            }
        }

        $timeout = $options['timeout'] ?? $this->timeout;

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl . '/chat/completions', $payload);
        } catch (Throwable $e) {
            throw new AIProviderException(
                'OpenAI request failed due to connection error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if ($response->failed()) {
            $errorMessage = $response->json('error.message')
                ?? $response->json('message')
                ?? 'HTTP error ' . $response->status();

            throw new AIProviderException(
                "OpenAI API error [{$response->status()}]: {$errorMessage}",
                $response->status()
            );
        }

        $data = $response->json();
        $choice = $data['choices'][0] ?? [];
        $message = $choice['message'] ?? [];

        return [
            'content' => $message['content'] ?? null,
            'role' => $message['role'] ?? 'assistant',
            'tool_calls' => $message['tool_calls'] ?? [],
            'finish_reason' => $choice['finish_reason'] ?? null,
            'usage' => $data['usage'] ?? [],
            'raw' => $data,
        ];
    }

    /**
     * Ensure the API key is present.
     *
     * @throws AIProviderException
     */
    protected function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new AIProviderException(
                'OpenAI API key is missing. Please set OPENAI_API_KEY in your .env or config/ecommerce-ai.php.'
            );
        }
    }

    /**
     * Validate chat messages structure.
     *
     * @param array<int, mixed> $messages
     * @throws AIProviderException
     */
    protected function validateMessages(array $messages): void
    {
        if (empty($messages)) {
            throw new AIProviderException('Messages array cannot be empty.');
        }

        foreach ($messages as $index => $message) {
            if (!is_array($message) || !isset($message['role'])) {
                throw new AIProviderException("Message at index {$index} must be an array containing a 'role' key.");
            }
        }
    }

    /**
     * Get current configured model.
     */
    public function getModel(): string
    {
        return $this->model;
    }
}

