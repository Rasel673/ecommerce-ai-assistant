<?php

namespace Rasel673\EcommerceAI\Support;

use Carbon\Carbon;
use InvalidArgumentException;

class DateRangeResolver
{
    /**
     * Resolve date range boundaries from period keyword or custom dates.
     *
     * @param array<string, mixed> $filters
     * @return array{
     *     period: string,
     *     start: Carbon|null,
     *     end: Carbon|null,
     *     start_string: string|null,
     *     end_string: string|null
     * }
     */
    public static function resolve(array $filters = []): array
    {
        $period = strtolower($filters['period'] ?? 'today');

        // Check for custom date range
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $period = 'custom';
            $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
            $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;

            if ($start && $end && $start->greaterThan($end)) {
                throw new InvalidArgumentException('start_date cannot be greater than end_date.');
            }

            return [
                'period' => $period,
                'start' => $start,
                'end' => $end,
                'start_string' => $start?->toDateTimeString(),
                'end_string' => $end?->toDateTimeString(),
            ];
        }

        switch ($period) {
            case 'today':
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                break;

            case 'yesterday':
                $start = Carbon::yesterday()->startOfDay();
                $end = Carbon::yesterday()->endOfDay();
                break;

            case 'this_week':
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;

            case 'this_month':
            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;

            case 'all_time':
            case 'all':
                $start = null;
                $end = null;
                break;

            default:
                $period = 'today';
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                break;
        }

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'start_string' => $start?->toDateTimeString(),
            'end_string' => $end?->toDateTimeString(),
        ];
    }
}

