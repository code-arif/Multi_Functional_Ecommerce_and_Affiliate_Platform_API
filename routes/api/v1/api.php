<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\AdminPanel\Http\Controllers\AffiliateProductController as AdminAffiliateProductController;
use Modules\AdminPanel\Http\Controllers\BannerController as AdminBannerController;
use Modules\AdminPanel\Http\Controllers\BrandController as AdminBrandController;
use Modules\AdminPanel\Http\Controllers\CmsPageController as AdminCmsPageController;
use Modules\AdminPanel\Http\Controllers\CouponController as AdminCouponController;
use Modules\AdminPanel\Http\Controllers\DashboardController as AdminDashboardController;
use Modules\AdminPanel\Http\Controllers\DisputeController as AdminDisputeController;
use Modules\AdminPanel\Http\Controllers\OrderController as AdminOrderController;
use Modules\AdminPanel\Http\Controllers\ProductController as AdminProductController;
use Modules\AdminPanel\Http\Controllers\ReportController as AdminReportController;
use Modules\AdminPanel\Http\Controllers\ReviewController as AdminReviewController;
use Modules\AdminPanel\Http\Controllers\SettingController as AdminSettingController;
use Modules\AdminPanel\Http\Controllers\UserController as AdminUserController;
use Modules\Affiliate\Http\Controllers\AdminAffiliateController;
use Modules\Cart\Http\Controllers\WishlistController;
use Modules\Cms\Http\Controllers\AdminCmsBlockController;
use Modules\Cms\Http\Controllers\AdminCmsMenuController;
use Modules\Promotions\Http\Controllers\AdminPromotionController;
use Modules\Support\Http\Controllers\ChatController;


// ─────────────────────────────────────────────────────────────────────────────
// AUTHENTICATED CUSTOMER ROUTES
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'banned'])->group(function () {

    // Wishlist
    Route::get('wishlist',             [WishlistController::class, 'index']);
    Route::post('wishlist',            [WishlistController::class, 'toggle']);
    Route::post('wishlist/move-to-cart', [WishlistController::class, 'moveToCart']);

    // Chat
    Route::get('chat/room',            [ChatController::class, 'myRoom']);
    Route::get('chat/room/{room}/messages',   [ChatController::class, 'messages']);
    Route::post('chat/room/{room}/messages',  [ChatController::class, 'sendMessage'])
        ->middleware('throttle:30,1');

    // Notifications
    Route::get('notifications', function (\Illuminate\Http\Request $req) {
        return response()->json([
            'success' => true,
            'data'    => $req->user()->notifications()->latest()->paginate(20),
            'unread'  => $req->user()->unreadNotifications()->count(),
        ]);
    });
    Route::post('notifications/{id}/read', function ($id, \Illuminate\Http\Request $req) {
        $req->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Marked as read.']);
    });
    Route::post('notifications/read-all', function (\Illuminate\Http\Request $req) {
        $req->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN ROUTES  — auth:sanctum + admin middleware
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'admin', 'banned'])->prefix('admin')->group(function () {

        // Dashboard
        Route::get('dashboard',             [AdminDashboardController::class, 'index']);

        // Products — permission-gated
        Route::post('products/upload-image', [AdminProductController::class, 'uploadImage'])->middleware('permission:products.create');

        Route::get('products', [AdminProductController::class, 'index'])->middleware('permission:products.view');
        Route::post('products', [AdminProductController::class, 'store'])->middleware('permission:products.create');
        Route::get('products/{product}', [AdminProductController::class, 'show'])->middleware('permission:products.view');
        Route::put('products/{product}', [AdminProductController::class, 'update'])->middleware('permission:products.edit');
        Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->middleware('permission:products.delete');

        // Orders
        Route::get('orders',[AdminOrderController::class, 'index'])->middleware('permission:orders.view');
        Route::get('orders/{order}',[AdminOrderController::class, 'show'])->middleware('permission:orders.view');
        Route::patch('orders/{order}/status',[AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.manage');
        Route::patch('orders/{order}/note',[AdminOrderController::class, 'updateAdminNote'])->middleware('permission:orders.manage');

        // Coupons
        Route::apiResource('coupons',       AdminCouponController::class)
            ->middleware('permission:coupons.manage');

        // Reviews
        Route::get('reviews',               [AdminReviewController::class, 'index'])->middleware('permission:reviews.view');
        Route::post('reviews/{review}/approve', [AdminReviewController::class, 'approve'])->middleware('permission:reviews.moderate');
        Route::post('reviews/{review}/reject', [AdminReviewController::class, 'reject'])->middleware('permission:reviews.moderate');
        Route::delete('reviews/{review}',   [AdminReviewController::class, 'destroy'])->middleware('permission:reviews.moderate');

        // Banners
        Route::get('banners', [AdminBannerController::class, 'index'])->middleware('permission:banners.view');
        Route::post('banners', [AdminBannerController::class, 'store'])->middleware('permission:banners.manage');
        Route::put('banners/{banner}', [AdminBannerController::class, 'update'])->middleware('permission:banners.manage');
        Route::delete('banners/{banner}', [AdminBannerController::class, 'destroy'])->middleware('permission:banners.manage');

        // Affiliate Products
        Route::get('affiliate-products', [AdminAffiliateProductController::class, 'index'])->middleware('permission:affiliate.manage');
        Route::post('affiliate-products/store', [AdminAffiliateProductController::class, 'store'])->middleware('permission:affiliate.manage');
        Route::put('affiliate-products/{affiliate_product}/update', [AdminAffiliateProductController::class, 'update'])->middleware('permission:affiliate.manage');
        Route::delete('affiliate-products/{affiliate_product}/delete', [AdminAffiliateProductController::class, 'destroy'])->middleware('permission:affiliate.manage');

        // CMS Pages
        Route::apiResource('pages', AdminCmsPageController::class)
            ->middleware('permission:cms.manage');

        // CMS Blocks
        Route::apiResource('blocks', AdminCmsBlockController::class)
            ->middleware('permission:cms.manage');

        // CMS Menus
        Route::apiResource('menus', AdminCmsMenuController::class)
            ->middleware('permission:cms.manage');

        // Promotions
        Route::get('promotions',                    [AdminPromotionController::class, 'index'])->middleware('permission:promotions.view');
        Route::post('promotions',                   [AdminPromotionController::class, 'store'])->middleware('permission:promotions.create');
        Route::get('promotions/analytics',          [AdminPromotionController::class, 'analytics'])->middleware('permission:promotions.view');

        // Affiliate
        Route::prefix('affiliate')->middleware('permission:affiliate.analytics')->group(function () {
            Route::get('analytics',                    [AdminAffiliateController::class, 'analytics']);
            Route::get('conversions',                  [AdminAffiliateController::class, 'conversions']);
            Route::post('conversions/{conversion}/approve', [AdminAffiliateController::class, 'approveConversion'])->middleware('permission:affiliate.manage');
            Route::post('conversions/{conversion}/reject',  [AdminAffiliateController::class, 'rejectConversion'])->middleware('permission:affiliate.manage');
            Route::get('earnings',                     [AdminAffiliateController::class, 'earnings']);
            Route::post('earnings/mark-paid',          [AdminAffiliateController::class, 'markAsPaid'])->middleware('permission:affiliate.manage');
        });
        Route::get('promotions/{promotion}',        [AdminPromotionController::class, 'show'])->middleware('permission:promotions.view');
        Route::put('promotions/{promotion}',        [AdminPromotionController::class, 'update'])->middleware('permission:promotions.edit');
        Route::post('promotions/{promotion}/toggle', [AdminPromotionController::class, 'toggle'])->middleware('permission:promotions.manage');
        Route::delete('promotions/{promotion}',     [AdminPromotionController::class, 'destroy'])->middleware('permission:promotions.delete');

        // Settings
        Route::get('settings',              [AdminSettingController::class, 'index'])->middleware('permission:settings.view');
        Route::post('settings',             [AdminSettingController::class, 'update'])->middleware('permission:settings.manage');
        Route::post('settings/upload',      [AdminSettingController::class, 'uploadFile'])->middleware('permission:settings.manage');

        // Users
        Route::get('users',                 [AdminUserController::class, 'index'])->middleware('permission:users.view');
        Route::get('users/{user}',          [AdminUserController::class, 'show'])->middleware('permission:users.view');
        Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->middleware('permission:users.ban');

        // Disputes
        Route::prefix('disputes')->middleware('permission:orders.manage')->group(function () {
            Route::get('/',                        [AdminDisputeController::class, 'index']);
            Route::get('{dispute}',                [AdminDisputeController::class, 'show']);
            Route::patch('{dispute}/status',       [AdminDisputeController::class, 'updateStatus']);
            Route::post('{dispute}/messages',      [AdminDisputeController::class, 'addMessage']);
        });

        // Reports
        Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
            Route::get('sales',             [AdminReportController::class, 'sales']);
            Route::get('top-products',      [AdminReportController::class, 'topProducts']);
            Route::get('orders-by-status',  [AdminReportController::class, 'ordersByStatus']);
            Route::get('customer-growth',   [AdminReportController::class, 'customerGrowth']);
        });

        // Chat (Admin side)
        Route::prefix('chat')->middleware('permission:chat.manage')->group(function () {
            Route::get('rooms',                  [ChatController::class, 'adminRooms']);
            Route::get('room/{room}/messages',   [ChatController::class, 'messages']);
            Route::post('room/{room}/messages',  [ChatController::class, 'sendMessage']);
            Route::post('room/{room}/close',     [ChatController::class, 'closeRoom']);
        });

        // Security (admin only — role:admin strict)
        Route::prefix('security')->middleware('role:admin')->group(function () {
            Route::post('unlock-account', function (\Illuminate\Http\Request $req) {
                $req->validate(['email' => 'required|email']);
                app(\Modules\Core\Services\Security\SecurityService::class)->unlockAccount($req->email);
                return response()->json(['success' => true, 'message' => 'Account unlocked.']);
            });
            Route::post('block-ip', function (\Illuminate\Http\Request $req) {
                $req->validate(['ip' => 'required|ip', 'duration' => 'nullable|integer', 'reason' => 'nullable|string']);
                app(\Modules\Core\Services\Security\SecurityService::class)
                    ->blockIp($req->ip_address, $req->duration ?? 3600, $req->reason ?? '');
                return response()->json(['success' => true, 'message' => 'IP blocked.']);
            });
            Route::post('revoke-tokens/{user}', function (User $user) {
                app(\Modules\Core\Services\Security\SecurityService::class)->revokeAllTokens($user->id);
                return response()->json(['success' => true, 'message' => 'All tokens revoked.']);
            });
        });
    });
