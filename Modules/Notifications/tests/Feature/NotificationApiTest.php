<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────

if (!function_exists('makeUser')) {
    function makeUser(string $email = 'notif@example.com'): User
    {
    return User::create([
        'name'     => 'Notification User',
        'email'    => $email,
        'phone'    => '+88017' . mt_rand(10000000, 99999999),
        'password' => bcrypt('password'),
        'status'   => 'active',
    ]);
    }
}

function createNotification(User $user, array $data = []): DatabaseNotification
{
    return $user->notifications()->create(array_merge([
        'id'      => (string) \Illuminate\Support\Str::uuid(),
        'type'    => 'App\Notifications\TestNotification',
        'data'    => ['message' => 'Test notification', 'action_url' => '/orders/1'],
        'read_at' => null,
    ], $data));
}

// ─── Notifications ──────────────────────────────────────────────

it('lists authenticated user notifications', function () {
    $user = makeUser('list-notif@example.com');
    createNotification($user);
    createNotification($user);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data', 'unread']);
});

it('returns unread count', function () {
    $user = makeUser('unread-count@example.com');
    createNotification($user); // unread
    createNotification($user); // unread
    createNotification($user, ['read_at' => now()]); // read

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications');

    $response->assertOk()
        ->assertJsonPath('unread', 2);
});

it('marks a notification as read', function () {
    $user = makeUser('mark-read@example.com');
    $notification = createNotification($user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/notifications/{$notification->id}/read");

    $response->assertOk()
        ->assertJsonPath('message', 'Marked as read.');

    $this->assertNotNull($notification->fresh()->read_at);
});

it('marks all notifications as read', function () {
    $user = makeUser('read-all@example.com');
    createNotification($user);
    createNotification($user);
    createNotification($user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/notifications/read-all');

    $response->assertOk()
        ->assertJsonPath('message', 'All notifications marked as read.');

    $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
});

it('returns empty list when no notifications', function () {
    $user = makeUser('no-notif@example.com');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications');

    $response->assertOk()
        ->assertJsonPath('unread', 0);
});

it('requires authentication for notifications', function () {
    $response = $this->getJson('/api/v1/notifications');
    $response->assertStatus(401);
});

it('requires authentication to mark read', function () {
    $response = $this->postJson('/api/v1/notifications/some-id/read');
    $response->assertStatus(401);
});

it('requires authentication to mark all read', function () {
    $response = $this->postJson('/api/v1/notifications/read-all');
    $response->assertStatus(401);
});
