<?php

namespace Rasel673\EcommerceAI\Contracts;

interface EcommerceAdapterInterface
{
    /**
     * Retrieve aggregated sales metrics for a specified period or custom range.
     *
     * @param array<string, mixed> $filters E.g., ['period' => 'today', 'start_date' => null, 'end_date' => null, 'store_id' => null]
     * @return array{
     *     period: string,
     *     total_orders: int,
     *     gross_sales: float,
     *     currency: string,
     *     start_date: string|null,
     *     end_date: string|null
     * }
     */
    public function getSalesSummary(array $filters = []): array;

    /**
     * Retrieve order counts grouped by status, along with average order value.
     *
     * @param array<string, mixed> $filters E.g., ['period' => 'this_month', 'store_id' => null]
     * @return array{
     *     total_orders: int,
     *     status_breakdown: array<string, int>,
     *     average_order_value: float,
     *     currency: string,
     *     period: string
     * }
     */
    public function getOrderSummary(array $filters = []): array;

    /**
     * Retrieve top-selling products by quantity sold or revenue.
     *
     * @param int $limit
     * @param array<string, mixed> $filters
     * @return array<int, array{
     *     id: int|string,
     *     name: string,
     *     sku: string|null,
     *     total_quantity_sold: int,
     *     total_revenue: float
     * }>
     */
    public function getTopSellingProducts(int $limit = 5, array $filters = []): array;

    /**
     * Retrieve products that have fallen below a specified stock threshold.
     *
     * @param int $threshold
     * @param int $limit
     * @param array<string, mixed> $filters
     * @return array<int, array{
     *     id: int|string,
     *     name: string,
     *     sku: string|null,
     *     current_stock: int,
     *     threshold: int
     * }>
     */
    public function getLowStockProducts(int $threshold = 10, int $limit = 10, array $filters = []): array;

    /**
     * Search product inventory by name or SKU.
     *
     * @param string $searchTerm
     * @param array<string, mixed> $filters
     * @return array<int, array{
     *     id: int|string,
     *     name: string,
     *     sku: string|null,
     *     current_stock: int,
     *     price: float,
     *     is_in_stock: bool
     * }>
     */
    public function getProductStock(string $searchTerm, array $filters = []): array;
}

