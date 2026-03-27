<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    public function getDashboardStats(): array
    {
        return Cache::remember('dashboard_stats', now()->addMinutes(10), function () {
            $today     = now()->toDateString();
            $thisMonth = now()->startOfMonth();
            $lastMonth = now()->subMonth()->startOfMonth();

            return [
                'today_sales'         => $this->getSalesTotal($today, $today),
                'monthly_sales'       => $this->getSalesTotal($thisMonth, now()),
                'total_orders'        => Order::whereNotIn('status', ['cancelled'])->count(),
                'pending_orders'      => Order::where('status', 'pending')->count(),
                'total_customers'     => User::whereHas('roles', fn($q) => $q->where('name', 'customer'))->count(),
                'total_products'      => Product::active()->count(),
                'low_stock_products'  => Product::active()
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->where('manage_stock', true)
                    ->count(),
                'revenue_this_month'  => $this->getSalesTotal($thisMonth, now()),
                'revenue_last_month'  => $this->getSalesTotal($lastMonth, now()->subMonth()->endOfMonth()),
            ];
        });
    }

    public function getSalesReport(string $period = 'daily', ?string $from = null, ?string $to = null): array
    {
        $from = $from ? \Carbon\Carbon::parse($from) : now()->subDays(30);
        $to   = $to   ? \Carbon\Carbon::parse($to)   : now();

        $groupBy = match ($period) {
            'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
            'yearly'  => "YEAR(created_at)",
            default   => "DATE(created_at)",
        };

        return Order::whereNotIn('status', ['cancelled'])
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw("{$groupBy} as date"),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('AVG(total_amount) as avg_order_value')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getTopProducts(int $limit = 10, ?string $from = null, ?string $to = null): array
    {
        $query = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNotIn('orders.status', ['cancelled'])
            ->select(
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_sold')
            ->limit($limit);

        if ($from) $query->where('orders.created_at', '>=', $from);
        if ($to)   $query->where('orders.created_at', '<=', $to);

        return $query->get()->toArray();
    }

    public function getOrdersByStatus(): array
    {
        return Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getCustomerGrowth(int $months = 12): array
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'customer'))
            ->where('created_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as new_customers')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function getSalesTotal(mixed $from, mixed $to): float
    {
        return (float) Order::whereNotIn('status', ['cancelled'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');
    }
}
