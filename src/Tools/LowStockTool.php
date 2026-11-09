<?php

namespace Rasel673\EcommerceAI\Tools;

use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Contracts\ToolInterface;

class LowStockTool implements ToolInterface
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
        return 'get_low_stock_products';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Retrieve products whose current inventory quantity is at or below a specified threshold, alerting on low stock.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'threshold' => [
                    'type' => 'integer',
                    'description' => 'Inventory threshold level (default: 10). Products with stock <= this value are returned.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of low stock products to return (default: 10, max: 50).',
                ],
            ],
            'required' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $arguments = [], array $context = []): array
    {
        $threshold = max(0, (int) ($arguments['threshold'] ?? config('ecommerce-ai.low_stock_threshold', 10)));
        $limit = max(1, min(50, (int) ($arguments['limit'] ?? 10)));

        $filters = [
            'store_id' => $context['store_id'] ?? $arguments['store_id'] ?? null,
            'tenant_id' => $context['tenant_id'] ?? $arguments['tenant_id'] ?? null,
        ];

        $products = $this->adapter->getLowStockProducts($threshold, $limit, $filters);

        return [
            'threshold' => $threshold,
            'count' => count($products),
            'products' => $products,
        ];
    }
}

