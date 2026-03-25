<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\Auth\AuthController;
use App\Http\Controllers\API\V1\Auth\AddressController;
use App\Http\Controllers\API\V1\Shop\ProductController;
use App\Http\Controllers\API\V1\Shop\CategoryController;
use App\Http\Controllers\API\V1\Shop\SearchController;
use App\Http\Controllers\API\V1\Shop\AffiliateController;
use App\Http\Controllers\API\V1\Cart\CartController;
use App\Http\Controllers\API\V1\Checkout\CheckoutController;
use App\Http\Controllers\API\V1\Order\OrderController;
use App\Http\Controllers\API\V1\Wishlist\WishlistController;
use App\Http\Controllers\API\V1\Review\ReviewController;
use App\Http\Controllers\API\V1\Chat\ChatController;
use App\Http\Controllers\API\V1\Cms\CmsController;
use App\Http\Controllers\API\V1\Cms\SeoController;
use App\Http\Controllers\API\V1\Admin;

// ─────────────────────────────────────────────────────────────────────────────
// PUBLIC ROUTES (No Authentication Required)
// ─────────────────────────────────────────────────────────────────────────────

// Auth — rate-limited to prevent brute force
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::post('admin/login', [AuthController::class, 'adminLogin']); // DONE: Admin login
});

// Products — public browsing
Route::prefix('products')->middleware('throttle:api')->group(function () {
    Route::get('/',               [ProductController::class, 'index']);
    Route::get('featured',        [ProductController::class, 'featured']);
    Route::get('new-arrivals',    [ProductController::class, 'newArrivals']);
    Route::get('bestsellers',     [ProductController::class, 'bestsellers']);
    Route::get('{slug}/reviews',  [ReviewController::class, 'index']);
    Route::get('{slug}/related',  [ProductController::class, 'related']);
    Route::get('{slug}',          [ProductController::class, 'show']);
});

// Categories
Route::prefix('categories')->middleware('throttle:api')->group(function () {
    Route::get('/',               [CategoryController::class, 'index']);
    Route::get('{slug}',          [CategoryController::class, 'show']);
});

// Search — separate throttle (higher load)
Route::prefix('search')->middleware('throttle:search')->group(function () {
    Route::get('/',               [SearchController::class, 'search']);
    Route::get('suggestions',     [SearchController::class, 'suggestions']);
    Route::get('price-range',     [SearchController::class, 'priceRange']);
    Route::get('facets',          [SearchController::class, 'facets']);
});

// Affiliate
Route::prefix('affiliate')->middleware('throttle:api')->group(function () {
    Route::get('/',               [AffiliateController::class, 'index']);
    Route::get('{slug}',          [AffiliateController::class, 'show']);
    Route::post('{slug}/click',   [AffiliateController::class, 'click']);
});

// CMS — public content
Route::prefix('cms')->middleware('throttle:api')->group(function () {
    Route::get('pages',           [CmsController::class, 'pages']);
    Route::get('pages/{slug}',    [CmsController::class, 'page']);
    Route::get('banners/{pos}',   [CmsController::class, 'banners']);
    Route::get('homepage',        [CmsController::class, 'homepage']);
    Route::get('settings',        [CmsController::class, 'settings']);
});

// SEO
Route::prefix('seo')->middleware('throttle:api')->group(function () {
    Route::get('homepage',        [SeoController::class, 'homepage']);
    Route::get('product/{slug}',  [SeoController::class, 'product']);
    Route::get('category/{slug}', [SeoController::class, 'category']);
    Route::get('page/{slug}',     [SeoController::class, 'page']);
});
Route::get('sitemap.xml',         [SeoController::class, 'sitemap']);

// Cart — accessible to guests via X-Session-ID header
Route::prefix('cart')->middleware('throttle:api')->group(function () {
    Route::get('/',                [CartController::class, 'index']);
    Route::post('items',           [CartController::class, 'addItem']);
    Route::put('items/{item}',     [CartController::class, 'updateItem']);
    Route::delete('items/{item}',  [CartController::class, 'removeItem']);
    Route::delete('/',             [CartController::class, 'clear']);
    Route::post('coupon',          [CartController::class, 'applyCoupon']);
    Route::delete('coupon',        [CartController::class, 'removeCoupon']);
});

// Checkout — stricter throttle to prevent order flooding
Route::prefix('checkout')->middleware('throttle:checkout')->group(function () {
    Route::post('/',               [CheckoutController::class, 'process']);
    Route::get('shipping-cost',    [CheckoutController::class, 'shippingCost']);
});

// Guest order tracking (no auth — uses token)
Route::get('orders/track/{token}', [OrderController::class, 'trackGuest'])
    ->middleware('throttle:api');

// ─────────────────────────────────────────────────────────────────────────────
// AUTHENTICATED CUSTOMER ROUTES
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'banned'])->group(function () {

    // Auth
    Route::post('auth/logout',         [AuthController::class, 'logout']);
    Route::post('auth/logout-all',     [AuthController::class, 'logoutAll']);
    Route::get('auth/me',              [AuthController::class, 'me']);
    Route::put('auth/profile',         [AuthController::class, 'updateProfile']);
    Route::post('auth/avatar',         [AuthController::class, 'updateAvatar']);

    // Addresses
    Route::apiResource('addresses',    AddressController::class);

    // Orders
    Route::get('orders',               [OrderController::class, 'index']);
    Route::get('orders/{number}',      [OrderController::class, 'show']);
    Route::post('orders/{number}/cancel', [OrderController::class, 'cancel']);

    // Wishlist
    Route::get('wishlist',             [WishlistController::class, 'index']);
    Route::post('wishlist',            [WishlistController::class, 'toggle']);
    Route::post('wishlist/move-to-cart', [WishlistController::class, 'moveToCart']);

    // Reviews — throttled to prevent spam
    Route::post('reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:10,1'); // 10 reviews per minute max

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

Route::middleware(['auth:sanctum', 'admin', 'banned'])
    ->prefix('admin')
    ->group(function () {

        // Dashboard
        Route::get('dashboard',             [Admin\DashboardController::class, 'index']);

        // Products — permission-gated
        Route::post('products/upload-image', [Admin\ProductController::class, 'uploadImage'])->middleware('permission:products.create'); // DONE: upload image

        Route::get('products', [Admin\ProductController::class, 'index'])->middleware('permission:products.view'); // DONE: product list
        Route::post('products', [Admin\ProductController::class, 'store'])->middleware('permission:products.create'); // DONE: product store
        Route::get('products/{product}', [Admin\ProductController::class, 'show'])->middleware('permission:products.view'); // DONE: product details
        Route::put('products/{product}', [Admin\ProductController::class, 'update'])->middleware('permission:products.edit'); // DONE: update product
        Route::delete('products/{product}', [Admin\ProductController::class, 'destroy'])->middleware('permission:products.delete'); // DONE: product delete

        // Categories
        Route::apiResource('categories',    Admin\CategoryController::class)
            ->middleware('permission:categories.manage');

        // Brands
        Route::apiResource('brands',        Admin\BrandController::class)
            ->middleware('permission:brands.manage');

        // Orders
        Route::get('orders',                [Admin\OrderController::class, 'index'])->middleware('permission:orders.view');
        Route::get('orders/{order}',        [Admin\OrderController::class, 'show'])->middleware('permission:orders.view');
        Route::patch('orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->middleware('permission:orders.manage');
        Route::patch('orders/{order}/note', [Admin\OrderController::class, 'updateAdminNote'])->middleware('permission:orders.manage');

        // Coupons
        Route::apiResource('coupons',       Admin\CouponController::class)
            ->middleware('permission:coupons.manage');

        // Reviews
        Route::get('reviews',               [Admin\ReviewController::class, 'index'])->middleware('permission:reviews.view');
        Route::post('reviews/{review}/approve', [Admin\ReviewController::class, 'approve'])->middleware('permission:reviews.moderate');
        Route::post('reviews/{review}/reject', [Admin\ReviewController::class, 'reject'])->middleware('permission:reviews.moderate');
        Route::delete('reviews/{review}',   [Admin\ReviewController::class, 'destroy'])->middleware('permission:reviews.moderate');

        // Banners
        Route::apiResource('banners',       Admin\BannerController::class)
            ->middleware('permission:banners.manage');

        // Affiliate Products
        Route::apiResource('affiliate-products', Admin\AffiliateProductController::class)
            ->middleware('permission:affiliate.manage');

        // CMS Pages
        Route::apiResource('pages',         Admin\CmsPageController::class)
            ->middleware('permission:cms.manage');

        // Settings
        Route::get('settings',              [Admin\SettingController::class, 'index'])->middleware('permission:settings.view');
        Route::post('settings',             [Admin\SettingController::class, 'update'])->middleware('permission:settings.manage');
        Route::post('settings/upload',      [Admin\SettingController::class, 'uploadFile'])->middleware('permission:settings.manage');

        // Users
        Route::get('users',                 [Admin\UserController::class, 'index'])->middleware('permission:users.view');
        Route::get('users/{user}',          [Admin\UserController::class, 'show'])->middleware('permission:users.view');
        Route::patch('users/{user}/status', [Admin\UserController::class, 'updateStatus'])->middleware('permission:users.ban');

        // Reports
        Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
            Route::get('sales',             [Admin\ReportController::class, 'sales']);
            Route::get('top-products',      [Admin\ReportController::class, 'topProducts']);
            Route::get('orders-by-status',  [Admin\ReportController::class, 'ordersByStatus']);
            Route::get('customer-growth',   [Admin\ReportController::class, 'customerGrowth']);
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
                app(\App\Services\Security\SecurityService::class)->unlockAccount($req->email);
                return response()->json(['success' => true, 'message' => 'Account unlocked.']);
            });
            Route::post('block-ip', function (\Illuminate\Http\Request $req) {
                $req->validate(['ip' => 'required|ip', 'duration' => 'nullable|integer', 'reason' => 'nullable|string']);
                app(\App\Services\Security\SecurityService::class)
                    ->blockIp($req->ip_address, $req->duration ?? 3600, $req->reason ?? '');
                return response()->json(['success' => true, 'message' => 'IP blocked.']);
            });
            Route::post('revoke-tokens/{user}', function (\App\Models\User $user) {
                app(\App\Services\Security\SecurityService::class)->revokeAllTokens($user->id);
                return response()->json(['success' => true, 'message' => 'All tokens revoked.']);
            });
        });
    });
