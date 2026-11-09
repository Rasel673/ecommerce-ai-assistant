<?php

namespace Rasel673\EcommerceAI\Tests\Unit;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Contracts\AIProviderInterface;
use Rasel673\EcommerceAI\Exceptions\AIProviderException;
use Rasel673\EcommerceAI\Providers\OpenAIProvider;
use Rasel673\EcommerceAI\Tests\TestCase;

class OpenAIProviderTest extends TestCase
{
    #[Test]
    public function it_resolves_ai_provider_from_container(): void
    {
        $provider = $this->app->make(AIProviderInterface::class);

        $this->assertInstanceOf(OpenAIProvider::class, $provider);
    }

    #[Test]
    public function it_sends_chat_request_and_normalizes_response(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-test',
                'object' => 'chat.completion',
                'created' => 1700000000,
                'model' => 'gpt-4o-mini',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Hello! How can I assist you with your store today?',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 14,
                    'total_tokens' => 26,
                ],
            ], 200),
        ]);

        $provider = new OpenAIProvider(apiKey: 'sk-test-valid-key');

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Hello'],
        ];

        $response = $provider->chat($messages);

        $this->assertEquals('Hello! How can I assist you with your store today?', $response['content']);
        $this->assertEquals('assistant', $response['role']);
        $this->assertEquals('stop', $response['finish_reason']);
        $this->assertEquals(26, $response['usage']['total_tokens']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer sk-test-valid-key')
                && $request['model'] === 'gpt-4o-mini'
                && count($request['messages']) === 2;
        });
    }

    #[Test]
    public function it_supports_configurable_model_and_temperature(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Response'],
                    ],
                ],
            ], 200),
        ]);

        $provider = new OpenAIProvider(apiKey: 'sk-test-valid-key', model: 'gpt-4o');

        $provider->chat(
            messages: [['role' => 'user', 'content' => 'Ping']],
            options: ['model' => 'gpt-4-turbo', 'temperature' => 0.2]
        );

        Http::assertSent(function ($request) {
            return $request['model'] === 'gpt-4-turbo'
                && $request['temperature'] === 0.2;
        });
    }

    #[Test]
    public function it_throws_exception_when_api_key_is_missing(): void
    {
        $this->expectException(AIProviderException::class);
        $this->expectExceptionMessage('OpenAI API key is missing');

        $provider = new OpenAIProvider(apiKey: '');
        $provider->chat([['role' => 'user', 'content' => 'Hello']]);
    }

    #[Test]
    public function it_throws_exception_when_messages_are_empty(): void
    {
        $this->expectException(AIProviderException::class);
        $this->expectExceptionMessage('Messages array cannot be empty');

        $provider = new OpenAIProvider(apiKey: 'sk-test-key');
        $provider->chat([]);
    }

    #[Test]
    public function it_handles_api_errors_safely(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Rate limit exceeded for requests',
                    'type' => 'rate_limit_exceeded',
                ],
            ], 429),
        ]);

        $this->expectException(AIProviderException::class);
        $this->expectExceptionMessage('OpenAI API error [429]: Rate limit exceeded for requests');

        $provider = new OpenAIProvider(apiKey: 'sk-test-key');
        $provider->chat([['role' => 'user', 'content' => 'Hello']]);
    }

    #[Test]
    public function it_does_not_leak_api_key_in_exception_messages(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response('Server error', 500),
        ]);

        $secretKey = 'sk-extremely-secret-key-12345';
        $provider = new OpenAIProvider(apiKey: $secretKey);

        try {
            $provider->chat([['role' => 'user', 'content' => 'Test']]);
            $this->fail('Expected AIProviderException was not thrown.');
        } catch (AIProviderException $e) {
            $this->assertStringNotContainsString($secretKey, $e->getMessage());
        }
    }
}

