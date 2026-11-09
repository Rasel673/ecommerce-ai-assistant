<?php

namespace Rasel673\EcommerceAI\Providers;

use Illuminate\Support\Facades\Http;
use Rasel673\EcommerceAI\Contracts\AIProviderInterface;
use Rasel673\EcommerceAI\Exceptions\AIProviderException;
use Throwable;

class GeminiProvider implements AIProviderInterface
{
    protected ?string $apiKey;
    protected string $model;
    protected int $timeout;
    protected ?float $temperature;
    protected string $baseUrl;

    public function __construct(
        ?string $apiKey = null,
        ?string $model = null,
        ?int $timeout = null,
        ?float $temperature = null,
        ?string $baseUrl = null
    ) {
        $this->apiKey = $apiKey ?? config('ecommerce-ai.providers.gemini.api_key', config('ecommerce-ai.gemini.api_key'));
        $this->model = $model ?? config('ecommerce-ai.providers.gemini.model', config('ecommerce-ai.gemini.model', 'gemini-1.5-flash'));
        $this->timeout = $timeout ?? (int) config('ecommerce-ai.providers.gemini.timeout', 30);
        $this->temperature = $temperature ?? config('ecommerce-ai.providers.gemini.temperature', 0.7);
        $this->baseUrl = rtrim($baseUrl ?? config('ecommerce-ai.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function chat(array $messages, array $options = []): array
    {
        $this->ensureApiKeyConfigured();

        $model = $options['model'] ?? $this->model;
        $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

        $systemInstruction = null;
        $contents = [];

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';

            if ($role === 'system') {
                $systemInstruction = [
                    'parts' => [
                        ['text' => (string) $content],
                    ],
                ];
            } elseif ($role === 'tool') {
                $contents[] = [
                    'role' => 'function',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $msg['name'] ?? 'tool_response',
                                'response' => is_array($content) ? $content : (json_decode($content, true) ?: ['result' => $content]),
                            ],
                        ],
                    ],
                ];
            } else {
                $parts = [];
                if (!empty($content)) {
                    $parts[] = ['text' => (string) $content];
                }

                if (!empty($msg['tool_calls'])) {
                    foreach ($msg['tool_calls'] as $tc) {
                        $args = is_array($tc['function']['arguments'])
                            ? $tc['function']['arguments']
                            : json_decode($tc['function']['arguments'] ?? '{}', true);

                        $parts[] = [
                            'functionCall' => [
                                'name' => $tc['function']['name'] ?? '',
                                'args' => $args ?: (object)[],
                            ],
                        ];
                    }
                }

                if (!empty($parts)) {
                    $contents[] = [
                        'role' => $role === 'assistant' ? 'model' : 'user',
                        'parts' => $parts,
                    ];
                }
            }
        }

        if (empty($contents)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => 'Hello']],
            ];
        }

        $payload = [
            'contents' => $contents,
        ];

        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        $temperature = $options['temperature'] ?? $this->temperature;
        if ($temperature !== null) {
            $payload['generationConfig']['temperature'] = (float) $temperature;
        }

        // Convert OpenAI-style tools to Gemini functionDeclarations format
        if (!empty($options['tools'])) {
            $payload['tools'] = [
                [
                    'functionDeclarations' => $this->formatToolsForGemini($options['tools']),
                ],
            ];
        }

        $timeout = $options['timeout'] ?? $this->timeout;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout($timeout)
              ->post($url, $payload);
        } catch (Throwable $e) {
            throw new AIProviderException(
                'Gemini request failed due to connection error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if ($response->failed()) {
            $errorMessage = $response->json('error.message') ?? 'HTTP error ' . $response->status();
            throw new AIProviderException(
                "Gemini API error [{$response->status()}]: {$errorMessage}",
                $response->status()
            );
        }

        $data = $response->json();
        $candidate = $data['candidates'][0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];

        $textContent = '';
        $normalizedToolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $textContent .= $part['text'];
            }
            if (isset($part['functionCall'])) {
                $normalizedToolCalls[] = [
                    'id' => uniqid('call_gemini_'),
                    'type' => 'function',
                    'function' => [
                        'name' => $part['functionCall']['name'] ?? '',
                        'arguments' => json_encode($part['functionCall']['args'] ?? []),
                    ],
                ];
            }
        }

        $usageMeta = $data['usageMetadata'] ?? [];

        return [
            'content' => !empty($textContent) ? $textContent : null,
            'role' => 'assistant',
            'tool_calls' => $normalizedToolCalls,
            'finish_reason' => $candidate['finishReason'] ?? null,
            'usage' => [
                'prompt_tokens' => $usageMeta['promptTokenCount'] ?? 0,
                'completion_tokens' => $usageMeta['candidatesTokenCount'] ?? 0,
                'total_tokens' => $usageMeta['totalTokenCount'] ?? 0,
            ],
            'raw' => $data,
        ];
    }

    /**
     * Convert OpenAI tool definitions to Gemini functionDeclarations.
     *
     * @param array<int, mixed> $tools
     * @return array<int, mixed>
     */
    protected function formatToolsForGemini(array $tools): array
    {
        $geminiTools = [];

        foreach ($tools as $tool) {
            if (isset($tool['function'])) {
                $geminiTools[] = [
                    'name' => $tool['function']['name'],
                    'description' => $tool['function']['description'] ?? '',
                    'parameters' => $tool['function']['parameters'] ?? (object)[],
                ];
            } elseif (isset($tool['name'])) {
                $geminiTools[] = $tool;
            }
        }

        return $geminiTools;
    }

    protected function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new AIProviderException('Gemini API key is missing. Set GEMINI_API_KEY in your .env.');
        }
    }
}
