<?php

namespace Rasel673\EcommerceAI\Adapters;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Rasel673\EcommerceAI\Contracts\EcommerceAdapterInterface;
use Rasel673\EcommerceAI\Support\DateRangeResolver;

class GenericEcommerceAdapter implements EcommerceAdapterInterface
{
    protected string $ordersTable;
    protected string $orderItemsTable;
    protected string $productsTable;
    protected string $currency;

    public function __construct(
        ?string $ordersTable = null,
        ?string $orderItemsTable = null,
        ?string $productsTable = null,
        ?string $currency = null
    ) {
        $this->ordersTable = $ordersTable ?? config('ecommerce-ai.tables.orders', 'orders');
        $this->orderItemsTable = $orderItemsTable ?? config('ecommerce-ai.tables.order_items', 'order_items');
        $this->productsTable = $productsTable ?? config('ecommerce-ai.tables.products', 'products');
        $this->currency = $currency ?? config('ecommerce-ai.currency', 'BDT');
    }

    /**
     * {@inheritdoc}
     */
    public function getSalesSummary(array $filters = []): array
    {
        $range = DateRangeResolver::resolve($filters);

        if (!Schema::hasTable($this->ordersTable)) {
            return [
                'period' => $range['period'],
                'total_orders' => 0,
                'gross_sales' => 0.0,
                'currency' => $this->currency,
                'start_date' => $range['start_string'],
                'end_date' => $range['end_string'],
            ];
        }

        $query = DB::table($this->ordersTable);

        // Apply date range
        if ($range['start'] && $range['end']) {
            $dateColumn = $this->resolveDateColumn($this->ordersTable);
            $query->whereBetween($dateColumn, [$range['start'], $range['end']]);
        }

        // Apply store/tenant scope
        $this->applyTenantScope($query, $this->ordersTable, $filters);

        // Calculate metrics
        $amountColumn = $this->resolveOrderAmountColumn($this->ordersTable);

        $totalOrders = (int) $query->count();
        $grossSales = (float) ($query->sum($amountColumn) ?? 0.0);

        return [
            'period' => $range['period'],
            'total_orders' => $totalOrders,
            'gross_sales' => round($grossSales, 2),
            'currency' => $this->currency,
            'start_date' => $range['start_string'],
            'end_date' => $range['end_string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderSummary(array $filters = []): array
    {
        $range = DateRangeResolver::resolve($filters);

        if (!Schema::hasTable($this->ordersTable)) {
            return [
                'total_orders' => 0,
                'status_breakdown' => [],
                'average_order_value' => 0.0,
                'currency' => $this->currency,
                'period' => $range['period'],
            ];
        }

        $query = DB::table($this->ordersTable);

        if ($range['start'] && $range['end']) {
            $dateColumn = $this->resolveDateColumn($this->ordersTable);
            $query->whereBetween($dateColumn, [$range['start'], $range['end']]);
        }

        $this->applyTenantScope($query, $this->ordersTable, $filters);

        $statusColumn = $this->resolveStatusColumn($this->ordersTable);
        $amountColumn = $this->resolveOrderAmountColumn($this->ordersTable);

        $totalOrders = (int) $query->count();
        $avgOrderValue = (float) ($query->avg($amountColumn) ?? 0.0);

        // Status breakdown
        $breakdown = [];
        if (Schema::hasColumn($this->ordersTable, $statusColumn)) {
            $statusCounts = (clone $query)
                ->select($statusColumn, DB::raw('count(*) as count'))
                ->groupBy($statusColumn)
                ->pluck('count', $statusColumn)
                ->toArray();

            foreach ($statusCounts as $status => $count) {
                $breakdown[(string) $status] = (int) $count;
            }
        }

        return [
            'total_orders' => $totalOrders,
            'status_breakdown' => $breakdown,
            'average_order_value' => round($avgOrderValue, 2),
            'currency' => $this->currency,
            'period' => $range['period'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTopSellingProducts(int $limit = 5, array $filters = []): array
    {
        if (!Schema::hasTable($this->orderItemsTable) || !Schema::hasTable($this->productsTable)) {
            return [];
        }

        $query = DB::table($this->orderItemsTable)
            ->join($this->productsTable, "{$this->orderItemsTable}.product_id", '=', "{$this->productsTable}.id");

        $range = DateRangeResolver::resolve($filters);
        if ($range['start'] && $range['end']) {
            $dateColumn = Schema::hasColumn($this->orderItemsTable, 'created_at')
                ? "{$this->orderItemsTable}.created_at"
                : "{$this->productsTable}.created_at";

            $query->whereBetween($dateColumn, [$range['start'], $range['end']]);
        }

        $this->applyTenantScope($query, $this->orderItemsTable, $filters);

        $nameColumn = Schema::hasColumn($this->productsTable, 'title') ? 'title' : 'name';
        $skuColumn = Schema::hasColumn($this->productsTable, 'sku') ? 'sku' : null;
        $qtyColumn = Schema::hasColumn($this->orderItemsTable, 'qty') ? 'qty' : 'quantity';
        $priceColumn = Schema::hasColumn($this->orderItemsTable, 'price') ? 'price' : 'unit_price';

        $selects = [
            "{$this->productsTable}.id",
            "{$this->productsTable}.{$nameColumn} as name",
            DB::raw("SUM({$this->orderItemsTable}.{$qtyColumn}) as total_quantity_sold"),
            DB::raw("SUM({$this->orderItemsTable}.{$qtyColumn} * {$this->orderItemsTable}.{$priceColumn}) as total_revenue"),
        ];

        if ($skuColumn) {
            $selects[] = "{$this->productsTable}.{$skuColumn} as sku";
        }

        $items = $query->select($selects)
            ->groupBy("{$this->productsTable}.id", "{$this->productsTable}.{$nameColumn}")
            ->orderByDesc('total_quantity_sold')
            ->limit($limit)
            ->get();

        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku ?? null,
                'total_quantity_sold' => (int) $item->total_quantity_sold,
                'total_revenue' => round((float) $item->total_revenue, 2),
            ];
        })->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getLowStockProducts(int $threshold = 10, int $limit = 10, array $filters = []): array
    {
        if (!Schema::hasTable($this->productsTable)) {
            return [];
        }

        $stockColumn = $this->resolveStockColumn($this->productsTable);
        $nameColumn = Schema::hasColumn($this->productsTable, 'title') ? 'title' : 'name';
        $skuColumn = Schema::hasColumn($this->productsTable, 'sku') ? 'sku' : null;

        $query = DB::table($this->productsTable)
            ->where($stockColumn, '<=', $threshold);

        $this->applyTenantScope($query, $this->productsTable, $filters);

        $selects = [
            'id',
            "{$nameColumn} as name",
            "{$stockColumn} as current_stock",
        ];

        if ($skuColumn) {
            $selects[] = 'sku';
        }

        $items = $query->select($selects)
            ->orderBy($stockColumn, 'asc')
            ->limit($limit)
            ->get();

        return $items->map(function ($item) use ($threshold) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku ?? null,
                'current_stock' => (int) $item->current_stock,
                'threshold' => $threshold,
            ];
        })->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductStock(string $searchTerm, array $filters = []): array
    {
        if (!Schema::hasTable($this->productsTable)) {
            return [];
        }

        $nameColumn = Schema::hasColumn($this->productsTable, 'title') ? 'title' : 'name';
        $skuColumn = Schema::hasColumn($this->productsTable, 'sku') ? 'sku' : null;
        $stockColumn = $this->resolveStockColumn($this->productsTable);
        $priceColumn = Schema::hasColumn($this->productsTable, 'price') ? 'price' : 'sale_price';

        $query = DB::table($this->productsTable)
            ->where(function ($q) use ($searchTerm, $nameColumn, $skuColumn) {
                $q->where($nameColumn, 'LIKE', "%{$searchTerm}%");
                if ($skuColumn) {
                    $q->orWhere($skuColumn, 'LIKE', "%{$searchTerm}%");
                }
            });

        $this->applyTenantScope($query, $this->productsTable, $filters);

        $selects = [
            'id',
            "{$nameColumn} as name",
            "{$stockColumn} as current_stock",
            "{$priceColumn} as price",
        ];

        if ($skuColumn) {
            $selects[] = 'sku';
        }

        $items = $query->select($selects)->limit(15)->get();

        return $items->map(function ($item) {
            $stock = (int) $item->current_stock;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku ?? null,
                'current_stock' => $stock,
                'price' => round((float) ($item->price ?? 0.0), 2),
                'is_in_stock' => $stock > 0,
            ];
        })->toArray();
    }

    /**
     * Apply optional store/tenant scoping.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $table
     * @param array<string, mixed> $filters
     */
    protected function applyTenantScope($query, string $table, array $filters): void
    {
        $tenantId = $filters['store_id'] ?? $filters['tenant_id'] ?? null;

        if ($tenantId !== null) {
            if (Schema::hasColumn($table, 'store_id')) {
                $query->where("{$table}.store_id", $tenantId);
            } elseif (Schema::hasColumn($table, 'tenant_id')) {
                $query->where("{$table}.tenant_id", $tenantId);
            }
        }
    }

    protected function resolveDateColumn(string $table): string
    {
        return Schema::hasColumn($table, 'created_at') ? "{$table}.created_at" : "{$table}.date";
    }

    protected function resolveOrderAmountColumn(string $table): string
    {
        if (Schema::hasColumn($table, 'total_amount')) {
            return 'total_amount';
        }
        if (Schema::hasColumn($table, 'total')) {
            return 'total';
        }
        if (Schema::hasColumn($table, 'grand_total')) {
            return 'grand_total';
        }

        return 'amount';
    }

    protected function resolveStatusColumn(string $table): string
    {
        return Schema::hasColumn($table, 'status') ? 'status' : 'order_status';
    }

    protected function resolveStockColumn(string $table): string
    {
        if (Schema::hasColumn($table, 'stock')) {
            return 'stock';
        }
        if (Schema::hasColumn($table, 'quantity')) {
            return 'quantity';
        }
        if (Schema::hasColumn($table, 'qty_available')) {
            return 'qty_available';
        }

        return 'stock';
    }
}

