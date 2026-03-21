<?php

use App\Http\Controllers\API\V1\Auth\AuthController;
use App\Http\Controllers\API\V1\Shop\CategoryController;
use App\Http\Controllers\API\V1\Shop\ProductController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ────────────────────────────────────────────

// Auth
Route::prefix('auth')->group(function () {
    Route::post('register',[AuthController::class, 'register']);
    Route::post('login',[AuthController::class, 'login']);
    Route::post('admin/login',[AuthController::class, 'adminLogin']);
});

// Shop — Products (public)
Route::prefix('products')->group(function () {
    Route::get('/',[ProductController::class, 'index']);
    Route::get('featured',[ProductController::class, 'featured']);
    Route::get('new-arrivals',[ProductController::class, 'newArrivals']);
    Route::get('bestsellers',[ProductController::class, 'bestsellers']);
    Route::get('{slug}',[ProductController::class, 'show']);
    Route::get('{slug}/related',[ProductController::class, 'related']);
});

// Shop — Categories (public)
Route::prefix('categories')->group(function () {
    Route::get('/',[CategoryController::class, 'index']);
    Route::get('{slug}',[CategoryController::class, 'show']);
});

// Search
Route::prefix('search')->group(function () {
    Route::get('/',             [\App\Http\Controllers\API\V1\Shop\SearchController::class, 'search']);
    Route::get('suggestions',   [\App\Http\Controllers\API\V1\Shop\SearchController::class, 'suggestions']);
    Route::get('price-range',   [\App\Http\Controllers\API\V1\Shop\SearchController::class, 'priceRange']);
});

// Affiliate
Route::prefix('affiliate')->group(function () {
    Route::get('/',             [\App\Http\Controllers\API\V1\Shop\AffiliateController::class, 'index']);
    Route::get('{slug}',        [\App\Http\Controllers\API\V1\Shop\AffiliateController::class, 'show']);
    Route::post('{slug}/click', [\App\Http\Controllers\API\V1\Shop\AffiliateController::class, 'click']);
});

// CMS
Route::prefix('cms')->group(function () {
    Route::get('pages',         [\App\Http\Controllers\API\V1\Cms\CmsController::class, 'pages']);
    Route::get('pages/{slug}',  [\App\Http\Controllers\API\V1\Cms\CmsController::class, 'page']);
    Route::get('banners/{position}', [\App\Http\Controllers\API\V1\Cms\CmsController::class, 'banners']);
    Route::get('homepage',      [\App\Http\Controllers\API\V1\Cms\CmsController::class, 'homepage']);
    Route::get('settings',      [\App\Http\Controllers\API\V1\Cms\CmsController::class, 'settings']);
});

// Reviews (public read)
Route::get('products/{slug}/reviews', [\App\Http\Controllers\API\V1\Review\ReviewController::class, 'index']);

// Cart (guest-accessible)
Route::prefix('cart')->group(function () {
    Route::get('/',             [\App\Http\Controllers\API\V1\Cart\CartController::class, 'index']);
    Route::post('items',        [\App\Http\Controllers\API\V1\Cart\CartController::class, 'addItem']);
    Route::put('items/{item}',  [\App\Http\Controllers\API\V1\Cart\CartController::class, 'updateItem']);
    Route::delete('items/{item}',[\App\Http\Controllers\API\V1\Cart\CartController::class, 'removeItem']);
    Route::delete('/',          [\App\Http\Controllers\API\V1\Cart\CartController::class, 'clear']);
    Route::post('coupon',       [\App\Http\Controllers\API\V1\Cart\CartController::class, 'applyCoupon']);
    Route::delete('coupon',     [\App\Http\Controllers\API\V1\Cart\CartController::class, 'removeCoupon']);
});

// Checkout (guest-accessible)
Route::prefix('checkout')->group(function () {
    Route::post('/',            [\App\Http\Controllers\API\V1\Checkout\CheckoutController::class, 'process']);
    Route::get('shipping-cost', [\App\Http\Controllers\API\V1\Checkout\CheckoutController::class, 'shippingCost']);
});

// Guest order tracking
Route::get('orders/track/{token}', [\App\Http\Controllers\API\V1\Order\OrderController::class, 'trackGuest']);

// ─── Authenticated Customer Routes ────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout',    [\App\Http\Controllers\API\V1\Auth\AuthController::class, 'logout']);
    Route::get('auth/me',         [\App\Http\Controllers\API\V1\Auth\AuthController::class, 'me']);
    Route::put('auth/profile',    [\App\Http\Controllers\API\V1\Auth\AuthController::class, 'updateProfile']);
    Route::post('auth/avatar',    [\App\Http\Controllers\API\V1\Auth\AuthController::class, 'updateAvatar']);

    // Addresses
    // Route::apiResource('addresses', \App\Http\Controllers\API\V1\Auth\AddressController::class);

    // Orders
    Route::get('orders',                [\App\Http\Controllers\API\V1\Order\OrderController::class, 'index']);
    Route::get('orders/{orderNumber}',  [\App\Http\Controllers\API\V1\Order\OrderController::class, 'show']);
    Route::post('orders/{orderNumber}/cancel', [\App\Http\Controllers\API\V1\Order\OrderController::class, 'cancel']);

    // Wishlist
    Route::get('wishlist',        [\App\Http\Controllers\API\V1\Wishlist\WishlistController::class, 'index']);
    Route::post('wishlist',       [\App\Http\Controllers\API\V1\Wishlist\WishlistController::class, 'toggle']);
    Route::post('wishlist/move-to-cart', [\App\Http\Controllers\API\V1\Wishlist\WishlistController::class, 'moveToCart']);

    // Reviews
    Route::post('reviews',        [\App\Http\Controllers\API\V1\Review\ReviewController::class, 'store']);

    // Chat
    Route::get('chat/room',       [\App\Http\Controllers\API\V1\Chat\ChatController::class, 'myRoom']);
    Route::get('chat/room/{room}/messages',  [\App\Http\Controllers\API\V1\Chat\ChatController::class, 'messages']);
    Route::post('chat/room/{room}/messages', [\App\Http\Controllers\API\V1\Chat\ChatController::class, 'sendMessage']);

    // Notifications
    Route::get('notifications',   function (\Illuminate\Http\Request $req) {
        return response()->json([
            'success' => true,
            'data'    => $req->user()->notifications()->latest()->paginate(20),
        ]);
    });
    Route::post('notifications/{id}/read', function ($id, \Illuminate\Http\Request $req) {
        $req->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    });
    Route::post('notifications/read-all', function (\Illuminate\Http\Request $req) {
        $req->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });
});

// ─── Admin Routes ─────────────────────────────────────────────

Route::middleware(['auth:sanctum', \App\Http\Middleware\AdminMiddleware::class])
     ->prefix('admin')
     ->group(function () {

    // Dashboard
    Route::get('dashboard', [\App\Http\Controllers\API\V1\Admin\DashboardController::class, 'index']);

    // Products
    Route::apiResource('products', \App\Http\Controllers\API\V1\Admin\ProductController::class);
    Route::post('products/upload-image', [\App\Http\Controllers\API\V1\Admin\ProductController::class, 'uploadImage']);

    // Categories
    Route::apiResource('categories', \App\Http\Controllers\API\V1\Admin\CategoryController::class);

    // Brands
    Route::apiResource('brands', \App\Http\Controllers\API\V1\Admin\BrandController::class);

    // Orders
    Route::get('orders',                  [\App\Http\Controllers\API\V1\Admin\OrderController::class, 'index']);
    Route::get('orders/{order}',          [\App\Http\Controllers\API\V1\Admin\OrderController::class, 'show']);
    Route::patch('orders/{order}/status', [\App\Http\Controllers\API\V1\Admin\OrderController::class, 'updateStatus']);
    Route::patch('orders/{order}/note',   [\App\Http\Controllers\API\V1\Admin\OrderController::class, 'updateAdminNote']);

    // Coupons
    Route::apiResource('coupons', \App\Http\Controllers\API\V1\Admin\CouponController::class);

    // Reviews
    Route::get('reviews',                   [\App\Http\Controllers\API\V1\Admin\ReviewController::class, 'index']);
    Route::post('reviews/{review}/approve', [\App\Http\Controllers\API\V1\Admin\ReviewController::class, 'approve']);
    Route::post('reviews/{review}/reject',  [\App\Http\Controllers\API\V1\Admin\ReviewController::class, 'reject']);
    Route::delete('reviews/{review}',       [\App\Http\Controllers\API\V1\Admin\ReviewController::class, 'destroy']);

    // Banners
    Route::apiResource('banners', \App\Http\Controllers\API\V1\Admin\BannerController::class);

    // Affiliate Products
    Route::apiResource('affiliate-products', \App\Http\Controllers\API\V1\Admin\AffiliateProductController::class);

    // CMS Pages
    Route::apiResource('pages', \App\Http\Controllers\API\V1\Admin\CmsPageController::class);

    // Settings
    Route::get('settings',          [\App\Http\Controllers\API\V1\Admin\SettingController::class, 'index']);
    Route::post('settings',         [\App\Http\Controllers\API\V1\Admin\SettingController::class, 'update']);
    Route::post('settings/upload',  [\App\Http\Controllers\API\V1\Admin\SettingController::class, 'uploadFile']);

    // Users
    Route::get('users',                     [\App\Http\Controllers\API\V1\Admin\UserController::class, 'index']);
    Route::get('users/{user}',              [\App\Http\Controllers\API\V1\Admin\UserController::class, 'show']);
    Route::patch('users/{user}/status',     [\App\Http\Controllers\API\V1\Admin\UserController::class, 'updateStatus']);

    // Reports
    Route::get('reports/sales',             [\App\Http\Controllers\API\V1\Admin\ReportController::class, 'sales']);
    Route::get('reports/top-products',      [\App\Http\Controllers\API\V1\Admin\ReportController::class, 'topProducts']);
    Route::get('reports/orders-by-status',  [\App\Http\Controllers\API\V1\Admin\ReportController::class, 'ordersByStatus']);
    Route::get('reports/customer-growth',   [\App\Http\Controllers\API\V1\Admin\ReportController::class, 'customerGrowth']);

    // Admin Chat
    Route::get('chat/rooms',                [\App\Http\Controllers\API\V1\Chat\ChatController::class, 'adminRooms']);
    Route::get('chat/room/{room}/messages', [\App\Http\Controllers\API\V1\Chat\ChatController::class, 'messages']);
    Route::post('chat/room/{room}/messages',[\App\Http\Controllers\API\V1\Chat\ChatController::class, 'sendMessage']);
    Route::post('chat/room/{room}/close',   [\App\Http\Controllers\API\V1\Chat\ChatController::class, 'closeRoom']);
});
