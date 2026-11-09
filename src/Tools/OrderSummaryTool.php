<?php

namespace Rasel673\EcommerceAI\Tools;

use DateTime;
use InvalidArgumentException;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Contracts\ToolInterface;

class OrderSummaryTool implements ToolInterface
{
    protected EcommerceAdapterInterface $adapter;

    protected const ALLOWED_PERIODS = [
        'today',
        'yesterday',
        'this_week',
        'this_month',
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
        return 'get_order_summary';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Retrieve total order count, average order value, and order status breakdown (e.g. pending, completed, cancelled) for a specified period or custom date range.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'period' => [
                    'type' => 'string',
                    'enum' => self::ALLOWED_PERIODS,
                    'description' => 'The time period to calculate order metrics for. Defaults to today.',
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
        $validated = $this->validateArguments($arguments);

        $filters = array_merge($validated, [
            'store_id' => $context['store_id'] ?? $arguments['store_id'] ?? null,
            'tenant_id' => $context['tenant_id'] ?? $arguments['tenant_id'] ?? null,
        ]);

        $summary = $this->adapter->getOrderSummary($filters);

        return [
            'period' => $summary['period'] ?? $validated['period'],
            'total_orders' => (int) ($summary['total_orders'] ?? 0),
            'status_breakdown' => $summary['status_breakdown'] ?? [],
            'average_order_value' => (float) ($summary['average_order_value'] ?? 0.0),
            'currency' => $summary['currency'] ?? config('ecommerce-ai.currency', 'BDT'),
        ];
    }

    /**
     * Validate arguments.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    protected function validateArguments(array $arguments): array
    {
        $period = strtolower($arguments['period'] ?? 'today');

        if (!in_array($period, self::ALLOWED_PERIODS, true)) {
            $period = 'today';
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

        if ($startDate || $endDate) {
            $period = 'custom';
        }

        return [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
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

