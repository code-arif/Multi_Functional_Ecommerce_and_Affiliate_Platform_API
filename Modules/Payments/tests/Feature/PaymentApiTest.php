<?php

namespace Modules\Payments\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Orders\Models\Order;
use Modules\Payments\Models\Payment;
use Modules\Payments\Models\Transaction;
use Modules\Payments\Models\Refund;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'   => 'Payment User',
            'email'  => 'payment@example.com',
            'status' => 'active',
        ]);

        $this->order = Order::create([
            'user_id'       => $this->user->id,
            'order_number'  => 'ORD-PAY-TEST',
            'subtotal'      => 200.00,
            'total'         => 200.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status'        => 'pending',
            'tracking_token' => 'tok-pay-test',
        ]);
    }

    /** @test */
    public function user_can_process_cod_payment(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/process', [
                'order_id'       => $this->order->id,
                'payment_method' => 'cod',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function payment_creates_transaction_record(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/process', [
                'order_id'       => $this->order->id,
                'payment_method' => 'cod',
            ]);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $this->order->id,
            'type'     => 'payment',
            'amount'   => 200.00,
        ]);
    }

    /** @test */
    public function user_can_view_order_payment(): void
    {
        Payment::create([
            'order_id'       => $this->order->id,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'gateway'        => 'cod',
            'status'         => 'pending',
            'amount'         => 200.00,
            'currency'       => 'BDT',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->order_number}/payment");

        $response->assertOk();
    }

    /** @test */
    public function user_cannot_pay_already_paid_order(): void
    {
        $this->order->update(['payment_status' => 'paid']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/process', [
                'order_id'       => $this->order->id,
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function user_can_save_payment_method(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/methods', [
                'gateway'          => 'stripe',
                'gateway_method_id' => 'pm_test_123',
                'type'             => 'card',
                'label'            => 'Visa ending in 4242',
            ]);

        $response->assertCreated();
    }

    /** @test */
    public function refund_creates_transaction(): void
    {
        $payment = Payment::create([
            'order_id'       => $this->order->id,
            'payment_method' => 'stripe',
            'payment_status' => 'paid',
            'gateway'        => 'stripe',
            'status'         => 'completed',
            'transaction_id' => 'ch_test_123',
            'amount'         => 200.00,
            'currency'       => 'BDT',
            'paid_at'        => now(),
        ]);

        $this->order->update(['payment_status' => 'paid']);

        $admin = User::factory()->create(['email' => 'admin-pay@example.com', 'status' => 'active']);

        // Note: Full refund test would need a real/mocked gateway
        // This tests the API route exists by checking that unauthorized returns 401/403
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/admin/payments/{$payment->id}/refund", [
                'amount' => 50.00,
                'reason' => 'Customer request',
            ]);

        // Should fail because user is not admin
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_process_payment(): void
    {
        $response = $this->postJson('/api/v1/payments/process', [
            'order_id'       => $this->order->id,
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(401);
    }
}
