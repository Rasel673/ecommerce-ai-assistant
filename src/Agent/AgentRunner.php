<?php

namespace Rasel673\EcommerceAI\Agent;

use Rasel673\EcommerceAI\Contracts\AIProviderInterface;
use Rasel673\EcommerceAI\Exceptions\AIProviderException;
use Rasel673\EcommerceAI\Registry\ToolRegistry;
use Throwable;

class AgentRunner
{
    protected AIProviderInterface $aiProvider;
    protected ToolRegistry $toolRegistry;
    protected int $maxIterations;

    public function __construct(
        AIProviderInterface $aiProvider,
        ToolRegistry $toolRegistry,
        ?int $maxIterations = null
    ) {
        $this->aiProvider = $aiProvider;
        $this->toolRegistry = $toolRegistry;
        $this->maxIterations = $maxIterations ?? (int) config('ecommerce-ai.agent.max_iterations', 5);
    }

    /**
     * Run the agent conversational loop with tool calling support.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, mixed> $context Execution context (e.g., store_id, tenant_id)
     * @param array<string, mixed> $options Custom options (e.g., model, temperature)
     * @return array{
     *     answer: string,
     *     tool_calls_executed: array<int, array<string, mixed>>,
     *     iterations: int,
     *     usage: array<string, mixed>
     * }
     *
     * @throws AIProviderException
     */
    public function run(array $messages, array $context = [], array $options = []): array
    {
        $iteration = 0;
        $executedToolCalls = [];
        $totalUsage = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ];

        // Format registered tools for OpenAI
        $availableTools = $this->toolRegistry->toOpenAITools();

        while ($iteration < $this->maxIterations) {
            $iteration++;

            $chatOptions = array_merge($options, [
                'tools' => !empty($availableTools) ? $availableTools : null,
            ]);

            $response = $this->aiProvider->chat($messages, $chatOptions);

            // Accumulate token usage if present
            if (!empty($response['usage'])) {
                $totalUsage['prompt_tokens'] += $response['usage']['prompt_tokens'] ?? 0;
                $totalUsage['completion_tokens'] += $response['usage']['completion_tokens'] ?? 0;
                $totalUsage['total_tokens'] += $response['usage']['total_tokens'] ?? 0;
            }

            $toolCalls = $response['tool_calls'] ?? [];

            // If the model did not request any tool calls, we have the final answer
            if (empty($toolCalls)) {
                return [
                    'answer' => $response['content'] ?? '',
                    'tool_calls_executed' => $executedToolCalls,
                    'iterations' => $iteration,
                    'usage' => $totalUsage,
                ];
            }

            // Append assistant response containing tool calls to conversation history
            $messages[] = [
                'role' => 'assistant',
                'content' => $response['content'] ?? null,
                'tool_calls' => $toolCalls,
            ];

            // Execute each requested tool call
            foreach ($toolCalls as $toolCall) {
                $toolCallId = $toolCall['id'] ?? uniqid('call_');
                $function = $toolCall['function'] ?? [];
                $functionName = $function['name'] ?? '';
                $rawArguments = $function['arguments'] ?? '{}';

                $arguments = is_array($rawArguments)
                    ? $rawArguments
                    : (json_decode($rawArguments, true) ?? []);

                $tool = $this->toolRegistry->get($functionName);

                if (!$tool) {
                    $toolResult = [
                        'error' => "Tool [{$functionName}] is not registered.",
                    ];
                } else {
                    try {
                        // Enforce application-controlled execution with context
                        $toolResult = $tool->execute($arguments, $context);
                    } catch (Throwable $e) {
                        $toolResult = [
                            'error' => 'Tool execution failed: ' . $e->getMessage(),
                        ];
                    }
                }

                $executedToolCalls[] = [
                    'id' => $toolCallId,
                    'name' => $functionName,
                    'arguments' => $arguments,
                    'result' => $toolResult,
                ];

                // Append tool execution result for the model
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCallId,
                    'content' => json_encode($toolResult),
                ];
            }
        }

        // If loop completes without natural termination, perform one final prompt for conclusion
        $finalResponse = $this->aiProvider->chat($messages, array_merge($options, ['tools' => null]));

        return [
            'answer' => $finalResponse['content'] ?? 'Here is the summary based on the retrieved information.',
            'tool_calls_executed' => $executedToolCalls,
            'iterations' => $iteration,
            'usage' => $totalUsage,
        ];
    }
}

