<?php

namespace Modules\AdminPanel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Models\User;
use Modules\Core\Traits\ApiResponse;
use Modules\Finance\Models\Commission;
use Modules\Orders\Models\Order;
use Modules\Product\Models\Product;
use Modules\Reviews\Models\Review;
use Modules\Vendor\Models\Vendor;

class DashboardController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->successResponse([
            // ─── Core Stats ─────────────────────────────────────
            'stats' => $this->getCoreStats(),

            // ─── Vendor Performance ─────────────────────────────
            'vendor_stats' => $this->getVendorStats(),
            'top_vendors'  => $this->getTopVendors(),

            // ─── Commission / Platform Revenue ──────────────────
            'commission_stats'        => $this->getCommissionStats(),
            'monthly_commission_trend' => $this->getMonthlyCommissionTrend(),

            // ─── Vendor Approval Queue ──────────────────────────
            'pending_vendors' => $this->getPendingVendors(),

            // ─── Recent Activity ────────────────────────────────
            'recent_orders' => Order::with('user')
                ->latest()
                ->limit(10)
                ->get(),
            'recent_users' => User::latest()
                ->limit(10)
                ->get(),
        ]);
    }

    private function getCoreStats(): array
    {
        return [
            'total_products'   => Product::count(),
            'total_orders'     => Order::count(),
            'total_revenue'    => Order::sum('total_amount'),
            'total_customers'  => User::count(),
            'total_vendors'    => Vendor::count(),
            'total_reviews'    => Review::count(),
            'pending_reviews'  => Review::pending()->count(),
        ];
    }

    private function getVendorStats(): array
    {
        return [
            'active_vendors'       => Vendor::active()->count(),
            'pending_approval'     => Vendor::pending()->count(),
            'total_earned'         => (float) Vendor::sum('total_earned'),
            'total_withdrawn'      => (float) Vendor::sum('total_withdrawn'),
            'total_wallet_balance' => (float) Vendor::sum('wallet_balance'),
            'avg_commission_rate'  => (float) (Vendor::active()->avg('commission_rate') ?? 0),
        ];
    }

    private function getTopVendors(): array
    {
        $topByRevenue = Vendor::select('vendors.id', 'vendors.shop_name', 'vendors.slug')
            ->selectRaw('COALESCE(SUM(orders.total_amount), 0) as revenue')
            ->selectRaw('COUNT(orders.id) as order_count')
            ->leftJoin('orders', 'vendors.id', '=', 'orders.vendor_id')
            ->where('vendors.status', 'active')
            ->whereIn('orders.status', ['delivered', 'shipped', 'confirmed'])
            ->groupBy('vendors.id', 'vendors.shop_name', 'vendors.slug')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn($v) => [
                'id'          => $v->id,
                'shop_name'   => $v->shop_name,
                'slug'        => $v->slug,
                'revenue'     => (float) $v->revenue,
                'order_count' => (int) $v->order_count,
            ])
            ->toArray();

        return ['by_revenue' => $topByRevenue];
    }

    private function getCommissionStats(): array
    {
        $totalCommission    = Commission::sum('commission_amount');
        $pendingCommission  = Commission::pending()->sum('commission_amount');
        $approvedCommission = Commission::approved()->sum('commission_amount');
        $totalOrdersValue   = Commission::sum('order_total');
        $commissionCount    = Commission::count();

        return [
            'total_commission'       => (float) $totalCommission,
            'pending_commission'     => (float) $pendingCommission,
            'approved_commission'    => (float) $approvedCommission,
            'total_orders_value'     => (float) $totalOrdersValue,
            'total_commission_lines' => $commissionCount,
            'effective_rate'         => $totalOrdersValue > 0
                ? round(($totalCommission / $totalOrdersValue) * 100, 2)
                : 0,
        ];
    }

    private function getMonthlyCommissionTrend(): array
    {
        $raw = Commission::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                SUM(commission_amount) as total,
                COUNT(*) as count
            ')
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($row) => [
                'month' => $row->month,
                'total' => (float) $row->total,
                'count' => (int) $row->count,
            ]);

        return $raw->toArray();
    }

    private function getPendingVendors(): array
    {
        return Vendor::with('user:id,name,email')
            ->pending()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($v) => [
                'id'              => $v->id,
                'shop_name'       => $v->shop_name,
                'slug'            => $v->slug,
                'owner_name'      => $v->user?->name,
                'owner_email'     => $v->user?->email,
                'commission_rate' => (float) $v->commission_rate,
                'created_at'      => $v->created_at,
            ])
            ->toArray();
    }
}
