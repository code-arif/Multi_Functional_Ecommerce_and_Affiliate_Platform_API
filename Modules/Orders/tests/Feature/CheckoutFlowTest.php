<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cart\Models\Cart;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\OrderService;
use Modules\Product\Models\Product;
use Modules\RBAC\Database\Seeders\RBACSeeder;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RBACSeeder::class);
});

if (!function_exists('makeCheckoutProduct')) {
    function makeCheckoutProduct(): Product
    {
        return Product::create([
            'name'               => 'Checkout Product',
            'slug'               => 'checkout-product-' . uniqid(),
            'sku'                => 'CHK-' . uniqid(),
            'type'               => 'simple',
            'price'              => 100,
            'sale_price'         => null,
            'stock_quantity'     => 50,
            'low_stock_threshold'=> 5,
            'manage_stock'       => true,
            'stock_status'       => 'in_stock',
            'status'             => 'active',
        ]);
    }
}

it('places an order through the checkout API with invoice and history', function () {
    $user = makeCustomerUser();
    $product = makeCheckoutProduct();

    $cart = Cart::create(['user_id' => $user->id]);
    $cart->items()->create([
        'product_id'  => $product->id,
        'quantity'    => 2,
        'unit_price'  => 100,
        'total_price' => 200,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/checkout', [
            'shipping_address' => [
                'address_line' => '123 Test St',
                'city'         => 'Dhaka',
                'country'      => 'Bangladesh',
            ],
            'payment_method' => 'cod',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $order = Order::first();

    expect($order)->not->toBeNull();
    expect($order->status)->toBe('pending');
    expect($order->items()->count())->toBe(1);
    expect($order->items()->first()->product_name)->toBe('Checkout Product');
    expect($order->statusHistories()->count())->toBe(1);
    expect($order->invoice)->not->toBeNull();
    expect($order->invoice->invoice_number)->toStartWith('INV-');
    expect($order->tracking_token)->not->toBeNull();
    expect($product->fresh()->stock_quantity)->toBe(48);
});

it('OrderService::placeOrder creates order, items, history and invoice', function () {
    $user = makeCustomerUser();

    $order = app(OrderService::class)->placeOrder([
        'user_id'          => $user->id,
        'order_number'     => Order::generateOrderNumber(),
        'status'           => 'pending',
        'subtotal'         => 300,
        'shipping_charge'  => 60,
        'discount_amount'  => 0,
        'coupon_discount'  => 0,
        'tax_amount'       => 15,
        'total_amount'     => 375,
        'payment_method'   => 'cod',
        'payment_status'   => 'pending',
        'shipping_name'    => 'Test User',
        'shipping_phone'   => '01700000000',
        'shipping_address_line1' => '123 Test St',
        'shipping_city'    => 'Dhaka',
        'shipping_country' => 'Bangladesh',
        'tracking_token'   => 'tok-' . uniqid(),
        'items' => [
            [
                'product_name' => 'Demo Product',
                'product_sku'  => 'DEMO-1',
                'unit_price'   => 150,
                'quantity'     => 2,
                'subtotal'     => 300,
            ],
        ],
        'actor_type' => 'customer',
        'actor_name' => $user->name,
    ]);

    expect($order->items()->count())->toBe(1);
    expect($order->statusHistories()->count())->toBe(1);
    expect($order->invoice)->not->toBeNull();
    expect($order->statusHistories()->first()->actor_type)->toBe('customer');
});
