<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Orders\Models\Order;
use Modules\Auth\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Reviews\Models\Review;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->successResponse([
            'stats' => [
                'total_products'  => Product::count(),
                'total_orders'    => Order::count(),
                'total_revenue'   => Order::sum('total_amount'),
                'total_customers' => User::count(),
                'total_reviews'   => Review::count(),
                'pending_reviews' => Review::pending()->count(),
            ],
            'recent_orders' => Order::with('user')
                ->latest()
                ->limit(10)
                ->get(),
            'recent_users' => User::latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
