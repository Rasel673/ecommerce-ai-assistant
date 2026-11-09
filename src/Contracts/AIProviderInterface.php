<?php

namespace Rasel673\EcommerceAI\Contracts;

interface AIProviderInterface
{
    /**
     * Send a chat completion request to the AI provider.
     *
     * @param array<int, array{role: string, content: string|null}> $messages
     * @param array<string, mixed> $options
     * @return array<string, mixed> Normalized response containing 'content', 'role', 'tool_calls', 'raw'
     *
     * @throws \Rasel673\EcommerceAI\Exceptions\AIProviderException
     */
    public function chat(array $messages, array $options = []): array;
}

