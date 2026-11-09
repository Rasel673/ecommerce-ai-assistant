<?php

namespace Rasel673\EcommerceAI\Contracts;

interface ToolInterface
{
    /**
     * Get the unique name of the tool (used by LLM function calling).
     */
    public function getName(): string;

    /**
     * Get a human and LLM-readable description of what the tool does.
     */
    public function getDescription(): string;

    /**
     * Get the JSON schema describing tool parameters (OpenAI compatible).
     *
     * @return array<string, mixed>
     */
    public function getParametersSchema(): array;

    /**
     * Execute the tool with validated arguments and execution context.
     *
     * @param array<string, mixed> $arguments Arguments passed by the LLM
     * @param array<string, mixed> $context Execution context (e.g. store_id, user_id)
     * @return array<string, mixed>
     */
    public function execute(array $arguments = [], array $context = []): array;
}

