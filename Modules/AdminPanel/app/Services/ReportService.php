<?php

namespace Modules\AdminPanel\Services;

use Modules\Orders\Models\Order;
use Modules\Auth\Models\User;
use Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function sales(array $filters = []): array
    {
        $query = Order::query();

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return [
            'total_orders'    => (clone $query)->count(),
            'total_revenue'   => (clone $query)->sum('total_amount'),
            'avg_order_value' => (clone $query)->avg('total_amount'),
            'orders_by_status' => (clone $query)
                ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as revenue'))
                ->groupBy('status')
                ->get()
                ->toArray(),
        ];
    }

    public function topProducts(int $limit = 10): array
    {
        return Product::select('products.*', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->groupBy('products.id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function ordersByStatus(): array
    {
        return Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->toArray();
    }

    public function customerGrowth(string $period = 'monthly'): array
    {
        $format = $period === 'daily' ? '%Y-%m-%d' : '%Y-%m';
        return User::select(
            DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
            DB::raw('count(*) as count')
        )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }
}
