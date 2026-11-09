<?php

namespace Rasel673\EcommerceAI\Tools;

use InvalidArgumentException;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Contracts\ToolInterface;

class ProductStockTool implements ToolInterface
{
    protected EcommerceAdapterInterface $adapter;

    public function __construct(EcommerceAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'get_product_stock';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Search for a specific product by name or SKU to inspect its current inventory level, price, and in-stock status.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'search' => [
                    'type' => 'string',
                    'description' => 'The product name, keyword, or SKU to search for.',
                ],
            ],
            'required' => ['search'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $arguments = [], array $context = []): array
    {
        $searchTerm = trim($arguments['search'] ?? $arguments['query'] ?? '');

        if (empty($searchTerm)) {
            throw new InvalidArgumentException('Search term cannot be empty.');
        }

        $filters = [
            'store_id' => $context['store_id'] ?? $arguments['store_id'] ?? null,
            'tenant_id' => $context['tenant_id'] ?? $arguments['tenant_id'] ?? null,
        ];

        $results = $this->adapter->getProductStock($searchTerm, $filters);

        return [
            'search' => $searchTerm,
            'found_count' => count($results),
            'products' => $results,
        ];
    }
}

