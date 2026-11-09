<?php

namespace Rasel673\EcommerceAI\Providers;

use Illuminate\Support\Facades\Http;
use Rasel673\EcommerceAI\Contracts\AIProviderInterface;
use Rasel673\EcommerceAI\Exceptions\AIProviderException;
use Throwable;

class AnthropicProvider implements AIProviderInterface
{
    protected ?string $apiKey;
    protected string $model;
    protected int $timeout;
    protected ?float $temperature;
    protected string $baseUrl;
    protected string $apiVersion;

    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeout = null,
        ?float $temperature = null,
        ?string $baseUrl = null,
        ?string $apiVersion = null
    ) {
        $this->apiKey = $apiKey ?? config('ecommerce-ai.providers.anthropic.api_key', config('ecommerce-ai.anthropic.api_key'));
        $this->model = $model ?? config('ecommerce-ai.providers.anthropic.model', config('ecommerce-ai.anthropic.model', 'claude-3-5-haiku-20241022'));
        $this->timeout = $timeout ?? (int) config('ecommerce-ai.providers.anthropic.timeout', 30);
        $this->temperature = $temperature ?? config('ecommerce-ai.providers.anthropic.temperature', 0.7);
        $this->baseUrl = rtrim($baseUrl ?? config('ecommerce-ai.providers.anthropic.base_url', 'https://api.anthropic.com/v1'), '/');
        $this->apiVersion = $apiVersion ?? config('ecommerce-ai.providers.anthropic.version', '2023-06-01');
    }

    /**
     * {@inheritdoc}
     */
    public function chat(array $messages, array $options = []): array
    {
        $this->ensureApiKeyConfigured();

        $systemPrompt = null;
        $formattedMessages = [];

        // Anthropic requires top-level system prompt and alternating user/assistant messages
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';

            if ($role === 'system') {
                $systemPrompt = ($systemPrompt ? $systemPrompt . "\n\n" : '') . $content;
            } elseif ($role === 'tool') {
                $formattedMessages[] = [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'tool_result',
                            'tool_use_id' => $msg['tool_call_id'] ?? '',
                            'content' => is_string($content) ? $content : json_encode($content),
                        ],
                    ],
                ];
            } elseif (!empty($msg['tool_calls'])) {
                $contentBlocks = [];
                if (!empty($content)) {
                    $contentBlocks[] = ['type' => 'text', 'text' => $content];
                }
                foreach ($msg['tool_calls'] as $tc) {
                    $args = is_array($tc['function']['arguments'])
                        ? $tc['function']['arguments']
                        : json_decode($tc['function']['arguments'] ?? '{}', true);

                    $contentBlocks[] = [
                        'type' => 'tool_use',
                        'id' => $tc['id'] ?? uniqid('tool_'),
                        'name' => $tc['function']['name'] ?? '',
                        'input' => $args ?: (object)[],
                    ];
                }
                $formattedMessages[] = [
                    'role' => 'assistant',
                    'content' => $contentBlocks,
                ];
            } else {
                $formattedMessages[] = [
                    'role' => $role === 'assistant' ? 'assistant' : 'user',
                    'content' => (string) $content,
                ];
            }
        }

        if (empty($formattedMessages)) {
            $formattedMessages[] = [
                'role' => 'user',
                'content' => 'Hello',
            ];
        }

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'max_tokens' => (int) ($options['max_tokens'] ?? 2048),
            'messages' => $formattedMessages,
        ];

        if ($systemPrompt !== null) {
            $payload['system'] = $systemPrompt;
        }

        $temperature = $options['temperature'] ?? $this->temperature;
        if ($temperature !== null) {
            $payload['temperature'] = (float) $temperature;
        }

        // Convert OpenAI-style tools to Anthropic format if provided
        if (!empty($options['tools'])) {
            $payload['tools'] = $this->formatToolsForAnthropic($options['tools']);
        }

        $timeout = $options['timeout'] ?? $this->timeout;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ])->timeout($timeout)
              ->post($this->baseUrl . '/messages', $payload);
        } catch (Throwable $e) {
            throw new AIProviderException(
                'Anthropic request failed due to connection error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if ($response->failed()) {
            $errorMessage = $response->json('error.message') ?? 'HTTP error ' . $response->status();
            throw new AIProviderException(
                "Anthropic API error [{$response->status()}]: {$errorMessage}",
                $response->status()
            );
        }

        $data = $response->json();
        $textContent = '';
        $normalizedToolCalls = [];

        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $textContent .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $normalizedToolCalls[] = [
                    'id' => $block['id'] ?? uniqid('call_'),
                    'type' => 'function',
                    'function' => [
                        'name' => $block['name'] ?? '',
                        'arguments' => json_encode($block['input'] ?? []),
                    ],
                ];
            }
        }

        return [
            'content' => !empty($textContent) ? $textContent : null,
            'role' => 'assistant',
            'tool_calls' => $normalizedToolCalls,
            'finish_reason' => $data['stop_reason'] ?? null,
            'usage' => [
                'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
            ],
            'raw' => $data,
        ];
    }

    /**
     * Convert OpenAI tool definitions to Anthropic tool definitions.
     *
     * @param array<int, mixed> $tools
     * @return array<int, mixed>
     */
    protected function formatToolsForAnthropic(array $tools): array
    {
        $anthropicTools = [];

        foreach ($tools as $tool) {
            if (isset($tool['function'])) {
                $anthropicTools[] = [
                    'name' => $tool['function']['name'],
                    'description' => $tool['function']['description'] ?? '',
                    'input_schema' => $tool['function']['parameters'] ?? (object)[],
                ];
            } elseif (isset($tool['name']) && isset($tool['input_schema'])) {
                $anthropicTools[] = $tool;
            }
        }

        return $anthropicTools;
    }

    protected function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new AIProviderException('Anthropic API key is missing. Set ANTHROPIC_API_KEY in your .env.');
        }
    }
}
