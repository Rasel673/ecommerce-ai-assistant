<?php

namespace Rasel673\EcommerceAI\Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Tests\TestCase;

class AgentToolCallingTest extends TestCase
{
    #[Test]
    public function it_executes_tool_call_flow_and_returns_natural_language_answer(): void
    {
        // 1. Mock the ecommerce adapter
        $mockAdapter = $this->createMock(EcommerceAdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('getSalesSummary')
            ->with($this->callback(function ($filters) {
                return $filters['period'] === 'today';
            }))
            ->willReturn([
                'period' => 'today',
                'total_orders' => 25,
                'gross_sales' => 45000.0,
                'currency' => 'BDT',
                'start_date' => '2026-11-09 00:00:00',
                'end_date' => '2026-11-09 23:59:59',
            ]);

        $this->app->instance(EcommerceAdapterInterface::class, $mockAdapter);

        // 2. Mock OpenAI HTTP responses:
        // Turn 1: Model decides to call get_sales_summary
        // Turn 2: Model receives tool output and gives natural language answer
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => null,
                                'tool_calls' => [
                                    [
                                        'id' => 'call_sales_123',
                                        'type' => 'function',
                                        'function' => [
                                            'name' => 'get_sales_summary',
                                            'arguments' => json_encode(['period' => 'today']),
                                        ],
                                    ],
                                ],
                            ],
                            'finish_reason' => 'tool_calls',
                        ],
                    ],
                ], 200)
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => 'Today you made 25 orders with total gross sales of 45,000 BDT.',
                            ],
                            'finish_reason' => 'stop',
                        ],
                    ],
                ], 200),
        ]);

        $response = $this->postJson('/api/ecommerce-ai/chat', [
            'message' => 'How much sales did I make today?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'answer' => 'Today you made 25 orders with total gross sales of 45,000 BDT.',
            ]);

        $this->assertCount(1, $response->json('tool_calls'));
        $this->assertEquals('get_sales_summary', $response->json('tool_calls.0.name'));
    }

    #[Test]
    public function it_handles_unknown_tool_calls_safely(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => null,
                                'tool_calls' => [
                                    [
                                        'id' => 'call_unknown_999',
                                        'type' => 'function',
                                        'function' => [
                                            'name' => 'execute_arbitrary_shell_command',
                                            'arguments' => '{}',
                                        ],
                                    ],
                                ],
                            ],
                            'finish_reason' => 'tool_calls',
                        ],
                    ],
                ], 200)
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => 'I cannot execute that action.',
                            ],
                            'finish_reason' => 'stop',
                        ],
                    ],
                ], 200),
        ]);

        $response = $this->postJson('/api/ecommerce-ai/chat', [
            'message' => 'Run a command',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'answer' => 'I cannot execute that action.',
            ]);

        $this->assertEquals('Tool [execute_arbitrary_shell_command] is not registered.', $response->json('tool_calls.0.result.error'));
    }

    #[Test]
    public function it_respects_max_iteration_safeguard(): void
    {
        $this->app['config']->set('ecommerce-ai.agent.max_iterations', 2);

        // Infinite tool loop simulation
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [
                                [
                                    'id' => 'call_loop',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'get_sales_summary',
                                        'arguments' => '{}',
                                    ],
                                ],
                            ],
                        ],
                        'finish_reason' => 'tool_calls',
                    ],
                ],
            ], 200),
        ]);

        $mockAdapter = $this->createMock(EcommerceAdapterInterface::class);
        $mockAdapter->method('getSalesSummary')->willReturn(['period' => 'today', 'total_orders' => 0, 'gross_sales' => 0.0, 'currency' => 'BDT']);
        $this->app->instance(EcommerceAdapterInterface::class, $mockAdapter);

        $response = $this->postJson('/api/ecommerce-ai/chat', [
            'message' => 'Loop me',
        ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('answer'));
    }
}

