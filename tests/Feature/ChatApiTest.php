<?php

namespace Rasel673\EcommerceAI\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Tests\TestCase;

class ChatApiTest extends TestCase
{
    #[Test]
    public function it_returns_successful_chat_response(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Hello! How can I help?',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/ecommerce-ai/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'answer' => 'Hello! How can I help?',
            ]);
    }

    #[Test]
    public function it_validates_required_message_field(): void
    {
        $response = $this->postJson('/api/ecommerce-ai/chat', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    #[Test]
    public function it_enforces_input_size_limit(): void
    {
        $this->app['config']->set('ecommerce-ai.max_input_length', 10);

        $response = $this->postJson('/api/ecommerce-ai/chat', [
            'message' => 'This message is longer than 10 characters',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    #[Test]
    public function it_returns_forbidden_when_package_is_disabled(): void
    {
        $this->app['config']->set('ecommerce-ai.enabled', false);

        $response = $this->postJson('/api/ecommerce-ai/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Ecommerce AI Assistant is currently disabled.',
            ]);
    }

    #[Test]
    public function it_handles_openai_provider_error_gracefully(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Quota exceeded',
                ],
            ], 429),
        ]);

        $response = $this->postJson('/api/ecommerce-ai/chat', [
            'message' => 'Hello',
        ]);

        $response->assertStatus(502)
            ->assertJsonStructure(['error']);
    }

    #[Test]
    public function it_passes_conversation_history_to_provider(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'You asked about shirts.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/ecommerce-ai/chat', [
            'message' => 'What was my previous question?',
            'history' => [
                ['role' => 'user', 'content' => 'Do you have red shirts?'],
                ['role' => 'assistant', 'content' => 'Yes, we have 15 in stock.'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'answer' => 'You asked about shirts.',
            ]);

        Http::assertSent(function ($request) {
            $messages = $request['messages'];
            // 1 system message + 2 history + 1 latest user message = 4 messages
            return count($messages) === 4
                && $messages[1]['content'] === 'Do you have red shirts?'
                && $messages[3]['content'] === 'What was my previous question?';
        });
    }
}

