<?php

namespace Rasel673\EcommerceAI\Tools;

use DateTime;
use InvalidArgumentException;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Contracts\ToolInterface;

class TopProductsTool implements ToolInterface
{
    protected EcommerceAdapterInterface $adapter;

    protected const ALLOWED_PERIODS = [
        'today',
        'yesterday',
        'this_week',
        'this_month',
        'all_time',
        'custom',
    ];

    public function __construct(EcommerceAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'get_top_products';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Retrieve top-selling products ranked by sales quantity and total revenue for a specified period or date range.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Number of top products to return (default: 5, max: 20).',
                ],
                'period' => [
                    'type' => 'string',
                    'enum' => self::ALLOWED_PERIODS,
                    'description' => 'Time period for ranking products. Defaults to this_month.',
                ],
                'start_date' => [
                    'type' => 'string',
                    'description' => 'Start date for custom range in YYYY-MM-DD format.',
                ],
                'end_date' => [
                    'type' => 'string',
                    'description' => 'End date for custom range in YYYY-MM-DD format.',
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
        $limit = max(1, min(20, (int) ($arguments['limit'] ?? 5)));
        $period = strtolower($arguments['period'] ?? 'this_month');

        if (!in_array($period, self::ALLOWED_PERIODS, true)) {
            $period = 'this_month';
        }

        $startDate = $arguments['start_date'] ?? null;
        $endDate = $arguments['end_date'] ?? null;

        if ($startDate !== null) {
            $this->validateDateFormat($startDate, 'start_date');
        }

        if ($endDate !== null) {
            $this->validateDateFormat($endDate, 'end_date');
        }

        if ($startDate && $endDate && $startDate > $endDate) {
            throw new InvalidArgumentException('start_date must be before or equal to end_date.');
        }

        $filters = [
            'period' => ($startDate || $endDate) ? 'custom' : $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'store_id' => $context['store_id'] ?? $arguments['store_id'] ?? null,
            'tenant_id' => $context['tenant_id'] ?? $arguments['tenant_id'] ?? null,
        ];

        $products = $this->adapter->getTopSellingProducts($limit, $filters);

        return [
            'count' => count($products),
            'limit' => $limit,
            'period' => $filters['period'],
            'products' => $products,
        ];
    }

    protected function validateDateFormat(string $date, string $field): void
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);

        if (!$d || $d->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException("Invalid {$field} format. Must be YYYY-MM-DD.");
        }
    }
}

