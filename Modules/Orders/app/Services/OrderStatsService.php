<?php

namespace Modules\Orders\Services;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Models\Order;

class OrderStatsService
{
    /**
     * Order overview statistics, optionally scoped to a single vendor.
     */
    public function overview(?int $vendorId = null): array
    {
        $base = Order::query()->when($vendorId, fn ($q) => $q->forVendor($vendorId));

        $live = OrderStatus::liveStatusValues();

        $statusCounts = (clone $base)
            ->select('status', DB::raw('COUNT(*) as order_count'))
            ->groupBy('status')
            ->pluck('order_count', 'status')
            ->toArray();

        $paymentCounts = (clone $base)
            ->select('payment_status', DB::raw('COUNT(*) as payment_count'))
            ->groupBy('payment_status')
            ->pluck('payment_count', 'payment_status')
            ->toArray();

        $today    = now()->toDateString();
        $monthStart = now()->startOfMonth();

        return [
            'total_orders'       => (clone $base)->count(),
            'status_counts'      => $statusCounts,
            'payment_counts'     => $paymentCounts,
            'pending_orders'     => $statusCounts['pending'] ?? 0,
            'today_orders'       => (clone $base)->whereDate('created_at', $today)->count(),
            'today_revenue'      => (float) (clone $base)
                ->whereDate('created_at', $today)
                ->whereIn('status', $live)
                ->sum('total_amount'),
            'month_orders'       => (clone $base)->where('created_at', '>=', $monthStart)->count(),
            'month_revenue'      => (float) (clone $base)
                ->where('created_at', '>=', $monthStart)
                ->whereIn('status', $live)
                ->sum('total_amount'),
            'total_revenue'      => (float) (clone $base)
                ->whereIn('status', $live)
                ->sum('total_amount'),
            'avg_order_value'    => round((float) ((clone $base)
                ->whereIn('status', $live)
                ->avg('total_amount') ?? 0), 2),
            'refunded_amount'    => (float) (clone $base)
                ->whereIn('status', ['refunded', 'cancelled'])
                ->sum('total_amount'),
        ];
    }
}
