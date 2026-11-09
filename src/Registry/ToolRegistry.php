<?php

namespace Rasel673\EcommerceAI\Registry;

use InvalidArgumentException;
use Rasel673\EcommerceAI\Contracts\ToolInterface;

class ToolRegistry
{
    /**
     * Map of registered tools keyed by tool name.
     *
     * @var array<string, ToolInterface>
     */
    protected array $tools = [];

    /**
     * Register a tool instance.
     *
     * @param ToolInterface $tool
     * @return self
     */
    public function register(ToolInterface $tool): self
    {
        $name = $tool->getName();

        if (empty($name)) {
            throw new InvalidArgumentException('Tool must have a valid non-empty name.');
        }

        $this->tools[$name] = $tool;

        return $this;
    }

    /**
     * Check if a tool is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Get a registered tool by name.
     */
    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get all registered tools.
     *
     * @return array<string, ToolInterface>
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Generate OpenAI-compatible tool definitions.
     *
     * @return array<int, array{type: string, function: array{name: string, description: string, parameters: array<string, mixed>}}>
     */
    public function toOpenAITools(): array
    {
        $definitions = [];

        foreach ($this->tools as $tool) {
            $definitions[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getParametersSchema(),
                ],
            ];
        }

        return $definitions;
    }
}

