<?php

namespace Rasel673\EcommerceAI\Tests\Unit;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Contracts\AIProviderInterface;
use Rasel673\EcommerceAI\Providers\AIProviderFactory;
use Rasel673\EcommerceAI\Providers\AnthropicProvider;
use Rasel673\EcommerceAI\Providers\DeepSeekProvider;
use Rasel673\EcommerceAI\Providers\GeminiProvider;
use Rasel673\EcommerceAI\Providers\GroqProvider;
use Rasel673\EcommerceAI\Providers\MistralProvider;
use Rasel673\EcommerceAI\Providers\OllamaProvider;
use Rasel673\EcommerceAI\Providers\OpenAIProvider;
use Rasel673\EcommerceAI\Tests\TestCase;

class MultiProviderTest extends TestCase
{
    #[Test]
    public function it_resolves_all_supported_providers_from_factory(): void
    {
        $this->assertInstanceOf(OpenAIProvider::class, AIProviderFactory::make('openai'));
        $this->assertInstanceOf(AnthropicProvider::class, AIProviderFactory::make('anthropic'));
        $this->assertInstanceOf(AnthropicProvider::class, AIProviderFactory::make('claude'));
        $this->assertInstanceOf(GeminiProvider::class, AIProviderFactory::make('gemini'));
        $this->assertInstanceOf(GeminiProvider::class, AIProviderFactory::make('google'));
        $this->assertInstanceOf(GroqProvider::class, AIProviderFactory::make('groq'));
        $this->assertInstanceOf(DeepSeekProvider::class, AIProviderFactory::make('deepseek'));
        $this->assertInstanceOf(OllamaProvider::class, AIProviderFactory::make('ollama'));
        $this->assertInstanceOf(MistralProvider::class, AIProviderFactory::make('mistral'));
    }

    #[Test]
    public function it_sends_chat_request_via_anthropic(): void
    {
        Http::fake([
            '*api.anthropic.com*' => Http::response([
                'id' => 'msg_123',
                'type' => 'message',
                'role' => 'assistant',
                'content' => [
                    ['type' => 'text', 'text' => 'Hello from Claude!'],
                ],
                'stop_reason' => 'end_turn',
                'usage' => ['input_tokens' => 15, 'output_tokens' => 20],
            ], 200),
        ]);

        $provider = new AnthropicProvider(apiKey: 'sk-ant-test-key');
        $response = $provider->chat([
            ['role' => 'system', 'content' => 'Be concise.'],
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertEquals('Hello from Claude!', $response['content']);
        $this->assertEquals(35, $response['usage']['total_tokens']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-key', 'sk-ant-test-key')
                && isset($request['system']);
        });
    }

    #[Test]
    public function it_handles_anthropic_tool_calls(): void
    {
        Http::fake([
            '*api.anthropic.com*' => Http::response([
                'content' => [
                    [
                        'type' => 'tool_use',
                        'id' => 'toolu_123',
                        'name' => 'get_sales_summary',
                        'input' => ['period' => 'today'],
                    ],
                ],
                'stop_reason' => 'tool_use',
            ], 200),
        ]);

        $provider = new AnthropicProvider(apiKey: 'sk-ant-test-key');
        $response = $provider->chat(
            messages: [['role' => 'user', 'content' => 'Sales today?']],
            options: [
                'tools' => [
                    [
                        'type' => 'function',
                        'function' => [
                            'name' => 'get_sales_summary',
                            'description' => 'Get sales',
                            'parameters' => ['type' => 'object'],
                        ],
                    ],
                ],
            ]
        );

        $this->assertCount(1, $response['tool_calls']);
        $this->assertEquals('get_sales_summary', $response['tool_calls'][0]['function']['name']);
    }

    #[Test]
    public function it_sends_chat_request_via_gemini(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Hello from Gemini!'],
                            ],
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 10,
                    'candidatesTokenCount' => 15,
                    'totalTokenCount' => 25,
                ],
            ], 200),
        ]);

        $provider = new GeminiProvider(apiKey: 'ai-gemini-key');
        $response = $provider->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertEquals('Hello from Gemini!', $response['content']);
        $this->assertEquals(25, $response['usage']['total_tokens']);
    }

    #[Test]
    public function it_handles_gemini_tool_calls(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'functionCall' => [
                                        'name' => 'get_order_summary',
                                        'args' => ['period' => 'this_week'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $provider = new GeminiProvider(apiKey: 'ai-gemini-key');
        $response = $provider->chat(
            messages: [['role' => 'user', 'content' => 'Orders this week?']],
            options: [
                'tools' => [
                    [
                        'type' => 'function',
                        'function' => ['name' => 'get_order_summary'],
                    ],
                ],
            ]
        );

        $this->assertCount(1, $response['tool_calls']);
        $this->assertEquals('get_order_summary', $response['tool_calls'][0]['function']['name']);
    }

    #[Test]
    public function it_sends_chat_request_via_groq(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Fast response from Groq!'],
                    ],
                ],
            ], 200),
        ]);

        $provider = new GroqProvider(apiKey: 'gsk_test_key');
        $response = $provider->chat([['role' => 'user', 'content' => 'Ping']]);

        $this->assertEquals('Fast response from Groq!', $response['content']);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer gsk_test_key'));
    }

    #[Test]
    public function it_sends_chat_request_via_deepseek(): void
    {
        Http::fake([
            'https://api.deepseek.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Response from DeepSeek!'],
                    ],
                ],
            ], 200),
        ]);

        $provider = new DeepSeekProvider(apiKey: 'sk-deepseek-key');
        $response = $provider->chat([['role' => 'user', 'content' => 'Ping']]);

        $this->assertEquals('Response from DeepSeek!', $response['content']);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer sk-deepseek-key'));
    }

    #[Test]
    public function it_sends_chat_request_via_ollama_without_requiring_key(): void
    {
        Http::fake([
            'http://localhost:11434/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Local response from Ollama!'],
                    ],
                ],
            ], 200),
        ]);

        $provider = new OllamaProvider(apiKey: '');
        $response = $provider->chat([['role' => 'user', 'content' => 'Hello Ollama']]);

        $this->assertEquals('Local response from Ollama!', $response['content']);
    }

    #[Test]
    public function it_sends_chat_request_via_mistral(): void
    {
        Http::fake([
            'https://api.mistral.ai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Response from Mistral!'],
                    ],
                ],
            ], 200),
        ]);

        $provider = new MistralProvider(apiKey: 'mistral-test-key');
        $response = $provider->chat([['role' => 'user', 'content' => 'Hello Mistral']]);

        $this->assertEquals('Response from Mistral!', $response['content']);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer mistral-test-key'));
    }
}
