<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Orders\Models\Order;
use Modules\Catalog\Models\Product;
use Modules\Support\Models\Ticket;
use Modules\Support\Models\Faq;
use Modules\Support\Models\FaqCategory;
use Modules\AdminPanel\Models\Dispute;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────

function seedSupportPermissions(): void
{
    app()[Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    if (!Role::where('name', 'super-admin')->exists()) {
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $permissions = [
            'support.tickets.view', 'support.tickets.manage',
            'support.faqs.manage',
            'support.chat.view', 'support.chat.manage',
            'support.disputes.view',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $superAdmin->givePermissionTo(Permission::all());
    }
}

function createOrder(User $customer): Order
{
    return Order::create([
        'user_id'        => $customer->id,
        'order_number'   => 'ORD-' . uniqid(),
        'subtotal'       => 100,
        'total_amount'   => 100,
        'status'         => 'delivered',
        'payment_status' => 'paid',
        'shipping_name'  => 'Test',
        'shipping_phone' => '123456',
        'shipping_address_line1' => '123 St',
        'shipping_city'  => 'City',
        'shipping_country' => 'BD',
    ]);
}

// ─── Public FAQs ────────────────────────────────────────────────

it('allows public to view FAQ categories', function () {
    seedSupportPermissions();
    FaqCategory::create(['name' => 'Shipping', 'slug' => 'shipping', 'is_active' => true]);

    $response = $this->getJson('/api/v1/support/faq-categories');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('allows public to view FAQs', function () {
    seedSupportPermissions();
    Faq::create(['question' => 'Test Q', 'answer' => 'Test A', 'is_active' => true]);

    $response = $this->getJson('/api/v1/support/faqs');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

// ─── Tickets ────────────────────────────────────────────────────

it('allows customer to create and view tickets', function () {
    seedSupportPermissions();
    $customer = makeCustomerUser();

    // Create
    $response = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/support/tickets', [
            'category'    => 'general',
            'subject'     => 'Need help',
            'description' => 'I need assistance with my order.',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $ticketId = $response->json('data.id');

    // List own tickets
    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/support/tickets');

    $response->assertOk()
        ->assertJsonPath('success', true);

    // Show ticket
    $response = $this->actingAs($customer, 'sanctum')
        ->getJson("/api/v1/support/tickets/{$ticketId}");

    $response->assertOk()
        ->assertJsonPath('data.id', $ticketId);

    // Add message
    $response = $this->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/support/tickets/{$ticketId}/messages", [
            'message' => 'More details: ...',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);
});

it('prevents customer from seeing other customers tickets', function () {
    seedSupportPermissions();
    $c1 = makeCustomerUser();
    $c2 = makeCustomerUser();

    $ticket = Ticket::create([
        'ticket_number' => Ticket::generateTicketNumber(),
        'user_id'       => $c1->id,
        'category'      => 'general',
        'subject'       => 'Private',
        'description'   => 'Secret',
        'priority'      => 'medium',
        'status'        => 'open',
    ]);

    $response = $this->actingAs($c2, 'sanctum')
        ->getJson("/api/v1/support/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

// ─── Admin Ticket Management ────────────────────────────────────

it('allows admin to manage tickets', function () {
    seedSupportPermissions();
    $admin = makeAdminUser();
    $customer = makeCustomerUser();

    $ticket = Ticket::create([
        'ticket_number' => Ticket::generateTicketNumber(),
        'user_id'       => $customer->id,
        'category'      => 'order',
        'subject'       => 'Order issue',
        'description'   => 'My order is late',
        'priority'      => 'high',
        'status'        => 'open',
    ]);

    // List all tickets
    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/support/tickets');

    $response->assertOk()
        ->assertJsonPath('success', true);

    // Show ticket
    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/support/tickets/{$ticket->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $ticket->id);

    // Reply as admin
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/support/tickets/{$ticket->id}/messages", [
            'message' => 'We are looking into it.',
        ]);

    $response->assertCreated();

    // Update status
    $response = $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/admin/support/tickets/{$ticket->id}/status", [
            'status' => 'in_progress',
        ]);

    $response->assertOk();
});

// ─── Customer Disputes ──────────────────────────────────────────

it('allows customer to submit and track disputes', function () {
    seedSupportPermissions();
    $customer = makeCustomerUser();
    $order = createOrder($customer);

    // Submit dispute
    $response = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/support/disputes', [
            'order_id'    => $order->id,
            'subject'     => 'Item not as described',
            'description' => 'Color was wrong',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $disputeId = $response->json('data.id');

    // List own disputes
    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/support/disputes');

    $response->assertOk()
        ->assertJsonPath('success', true);

    // Show dispute
    $response = $this->actingAs($customer, 'sanctum')
        ->getJson("/api/v1/support/disputes/{$disputeId}");

    $response->assertOk()
        ->assertJsonPath('data.id', $disputeId);
});

it('prevents customer from submitting dispute for others order', function () {
    seedSupportPermissions();
    $c1 = makeCustomerUser();
    $c2 = makeCustomerUser();
    $order = createOrder($c1);

    $response = $this->actingAs($c2, 'sanctum')
        ->postJson('/api/v1/support/disputes', [
            'order_id'    => $order->id,
            'subject'     => 'Not mine',
            'description' => 'This is not my order',
        ]);

    $response->assertStatus(403);
});

// ─── Admin FAQ Management ───────────────────────────────────────

it('allows admin to manage FAQs and categories', function () {
    seedSupportPermissions();
    $admin = makeAdminUser();

    // Create FAQ category
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/support/faq-categories', [
            'name'      => 'Shipping',
            'is_active' => true,
        ]);

    $response->assertCreated();

    $catId = $response->json('data.id');

    // Create FAQ
    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/support/faqs', [
            'category_id' => $catId,
            'question'    => 'How long does shipping take?',
            'answer'      => '2-3 business days.',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $faqId = $response->json('data.id');

    // List FAQs
    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/support/faqs');

    $response->assertOk();

    // Show FAQ
    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/support/faqs/{$faqId}");

    $response->assertOk();

    // Update FAQ
    $response = $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/support/faqs/{$faqId}", [
            'answer' => '1-2 business days.',
        ]);

    $response->assertOk();

    // Delete FAQ
    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/support/faqs/{$faqId}");

    $response->assertOk();
});

// ─── Customer Chat ──────────────────────────────────────────────

it('allows customer to use chat', function () {
    seedSupportPermissions();
    $customer = makeCustomerUser();

    // Create room
    $response = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/support/chat/room', [
            'subject' => 'Need help with my order',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    // Get my room
    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/support/chat/room');

    $response->assertOk();

    $roomId = $response->json('data.id');

    // Send message
    $response = $this->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/support/chat/room/{$roomId}/messages", [
            'message' => 'Hello, I need help!',
        ]);

    $response->assertCreated();

    // Get messages
    $response = $this->actingAs($customer, 'sanctum')
        ->getJson("/api/v1/support/chat/room/{$roomId}/messages");

    $response->assertOk();
});

// ─── Authorization ──────────────────────────────────────────────

it('requires authentication for ticket routes', function () {
    $response = $this->postJson('/api/v1/support/tickets', [
        'category'    => 'general',
        'subject'     => 'Test',
        'description' => 'Test',
    ]);
    $response->assertStatus(401);
});

it('requires authentication for dispute routes', function () {
    $response = $this->postJson('/api/v1/support/disputes', [
        'order_id'    => 1,
        'subject'     => 'Test',
        'description' => 'Test',
    ]);
    $response->assertStatus(401);
});

it('requires admin for FAQ management', function () {
    seedSupportPermissions();
    $customer = makeCustomerUser();

    $response = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/admin/support/faqs', [
            'question' => 'Test',
            'answer'   => 'Test',
        ]);

    $response->assertStatus(403);
});
