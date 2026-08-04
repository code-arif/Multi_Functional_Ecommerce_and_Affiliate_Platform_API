<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Vendor\Models\Vendor;
use Modules\Orders\Models\Order;
use Modules\Product\Models\Product;;
use Modules\Reviews\Models\Review;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorDashboardController
{
    use ApiResponse;

    /**
     * GET /api/v1/vendor/dashboard
     * Get dashboard stats for the authenticated vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }

        return $this->successResponse([
            'stats' => $this->getCoreStats($vendor),
            'recent_orders' => $this->getRecentOrders($vendor),
            'monthly_revenue_trend' => $this->getMonthlyRevenueTrend($vendor),
            'top_products' => $this->getTopProducts($vendor),
        ]);
    }

    private function getCoreStats(Vendor $vendor): array
    {
        $totalOrders = Order::where('vendor_id', $vendor->id)->count();
        $totalRevenue = Order::where('vendor_id', $vendor->id)
            ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
            ->sum('total_amount');
        $pendingOrders = Order::where('vendor_id', $vendor->id)
            ->where('status', 'pending')->count();
        $processingOrders = Order::where('vendor_id', $vendor->id)
            ->where('status', 'processing')->count();

        $productCount = Product::whereHas('vendorProductPrices', fn($q) => $q->where('vendor_id', $vendor->id))->count();
        $approvedReviews = Review::whereHas('product.vendorProductPrices', fn($q) => $q->where('vendor_id', $vendor->id))
            ->where('status', 'approved')->count();
        $pendingReviews = Review::whereHas('product.vendorProductPrices', fn($q) => $q->where('vendor_id', $vendor->id))
            ->where('status', 'pending')->count();

        return [
            'total_orders'      => $totalOrders,
            'total_revenue'     => (float) $totalRevenue,
            'pending_orders'    => $pendingOrders,
            'processing_orders' => $processingOrders,
            'total_products'    => $productCount,
            'total_reviews'     => $approvedReviews,
            'pending_reviews'   => $pendingReviews,
            'wallet_balance'    => (float) $vendor->wallet_balance,
            'total_earned'      => (float) $vendor->total_earned,
            'total_withdrawn'   => (float) $vendor->total_withdrawn,
        ];
    }

    private function getRecentOrders(Vendor $vendor): array
    {
        return Order::with('user:id,name,email')
            ->where('vendor_id', $vendor->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($order) => [
                'id'             => $order->id,
                'order_number'   => $order->order_number,
                'total_amount'   => (float) $order->total_amount,
                'status'         => $order->status,
                'payment_status' => $order->payment_status,
                'customer_name'  => $order->user?->name,
                'created_at'     => $order->created_at,
            ])
            ->toArray();
    }

    private function getMonthlyRevenueTrend(Vendor $vendor): array
    {
        $raw = Order::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                SUM(total_amount) as revenue,
                COUNT(*) as order_count
            ')
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['delivered', 'shipped', 'confirmed'])
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($row) => [
                'month'       => $row->month,
                'revenue'     => (float) $row->revenue,
                'order_count' => (int) $row->order_count,
            ]);

        return $raw->toArray();
    }

    private function getTopProducts(Vendor $vendor): array
    {
        return Product::select('products.id', 'products.name', 'products.slug', 'products.thumbnail')
            ->selectRaw('COUNT(order_items.id) as total_sold')
            ->selectRaw('COALESCE(SUM(order_items.total), 0) as revenue')
            ->join('vendor_product_prices', function ($join) use ($vendor) {
                $join->on('products.id', '=', 'vendor_product_prices.product_id')
                    ->where('vendor_product_prices.vendor_id', $vendor->id)
                    ->where('vendor_product_prices.is_active', true);
            })
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereIn('orders.status', ['delivered', 'shipped', 'confirmed']);
            })
            ->groupBy('products.id', 'products.name', 'products.slug', 'products.thumbnail')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'thumbnail'   => $p->thumbnail_url,
                'total_sold'  => (int) $p->total_sold,
                'revenue'     => (float) $p->revenue,
            ])
            ->toArray();
    }
}
