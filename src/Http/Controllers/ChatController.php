<?php

namespace Rasel673\EcommerceAI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rasel673\EcommerceAI\Agent\AgentRunner;
use Rasel673\EcommerceAI\Exceptions\AIProviderException;
use Throwable;

class ChatController extends Controller
{
    protected AgentRunner $agentRunner;

    /**
     * Create a new ChatController instance.
     *
     * @param AgentRunner $agentRunner
     */
    public function __construct(AgentRunner $agentRunner)
    {
        $this->agentRunner = $agentRunner;
    }

    /**
     * Handle incoming chat message.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function chat(Request $request): JsonResponse
    {
        if (!config('ecommerce-ai.enabled', true)) {
            return response()->json([
                'error' => 'Ecommerce AI Assistant is currently disabled.',
            ], 403);
        }

        $maxLength = (int) config('ecommerce-ai.max_input_length', 2000);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', "max:{$maxLength}"],
            'history' => ['sometimes', 'array', 'max:20'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant,system'],
            'history.*.content' => ['required_with:history', 'string'],
            'store_id' => ['sometimes', 'nullable'],
        ]);

        $messages = [];

        // Prepend system prompt
        $systemPrompt = config(
            'ecommerce-ai.system_prompt',
            'You are a helpful ecommerce AI assistant for this online store.'
        );

        if (!empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        // Append historical conversation context if supplied
        if (!empty($validated['history'])) {
            foreach ($validated['history'] as $item) {
                $messages[] = [
                    'role' => $item['role'],
                    'content' => $item['content'],
                ];
            }
        }

        // Append latest user message
        $messages[] = [
            'role' => 'user',
            'content' => $validated['message'],
        ];

        // Determine context (e.g. authenticated user's store_id or request param)
        $context = [
            'store_id' => $request->user()?->store_id ?? $validated['store_id'] ?? null,
            'tenant_id' => $request->user()?->tenant_id ?? null,
        ];

        try {
            $result = $this->agentRunner->run($messages, $context);

            return response()->json([
                'answer' => $result['answer'] ?? '',
                'tool_calls' => $result['tool_calls_executed'] ?? [],
            ], 200);
        } catch (AIProviderException $e) {
            return response()->json([
                'error' => 'AI Service Error: ' . $e->getMessage(),
            ], 502);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An unexpected error occurred while processing your request.',
            ], 500);
        }
    }
}

