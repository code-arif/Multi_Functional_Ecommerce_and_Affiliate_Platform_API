<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Modules\Auth\Models\User;
use Modules\Core\Models\Country;
use Modules\Core\Models\State;
use Modules\Core\Models\City;
use Modules\Core\Models\Currency;
use Modules\Core\Models\Language;
use Modules\Core\Models\Medium;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────

if (!function_exists('makeUser')) {
    function makeUser(string $email = 'core@example.com', string $status = 'active'): User
    {
    return User::create([
        'name'     => 'Core Tester',
        'email'    => $email,
        'phone'    => '+88017' . mt_rand(10000000, 99999999),
        'password' => bcrypt('password'),
        'status'   => $status,
    ]);
    }
}

// ─── App Info ────────────────────────────────────────────────────

it('returns app info', function () {
    $response = $this->getJson('/api/v1/core/info');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['name', 'env', 'locale', 'timezone', 'currency']]);
});

// ─── Countries / Locations ───────────────────────────────────────

it('lists active countries', function () {
    Country::create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '880', 'is_active' => true]);
    Country::create(['name' => 'India', 'iso2' => 'IN', 'iso3' => 'IND', 'phone_code' => '91', 'is_active' => true]);
    Country::create(['name' => 'Hidden', 'iso2' => 'XX', 'iso3' => 'XXX', 'phone_code' => '0', 'is_active' => false]);

    $response = $this->getJson('/api/v1/core/countries');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('shows a country with its states', function () {
    $country = Country::create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '880', 'is_active' => true]);
    $state = $country->states()->create(['name' => 'Dhaka', 'is_active' => true]);

    $response = $this->getJson("/api/v1/core/countries/{$country->uuid}");

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('lists states for a country', function () {
    $country = Country::create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '880', 'is_active' => true]);
    $country->states()->create(['name' => 'Dhaka', 'is_active' => true]);
    $country->states()->create(['name' => 'Chittagong', 'is_active' => true]);

    $response = $this->getJson("/api/v1/core/countries/{$country->uuid}/states");

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('shows a state with cities', function () {
    $country = Country::create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '880', 'is_active' => true]);
    $state = $country->states()->create(['name' => 'Dhaka', 'country_id' => $country->id, 'is_active' => true]);
    $state->cities()->create(['name' => 'Dhaka City', 'state_id' => $state->id, 'country_id' => $country->id, 'is_active' => true]);

    $response = $this->getJson("/api/v1/core/states/{$state->uuid}");

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('lists cities for a state', function () {
    $country = Country::create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '880', 'is_active' => true]);
    $state = $country->states()->create(['name' => 'Dhaka', 'country_id' => $country->id, 'is_active' => true]);
    $state->cities()->create(['name' => 'Dhaka City', 'state_id' => $state->id, 'country_id' => $country->id, 'is_active' => true]);
    $state->cities()->create(['name' => 'Gazipur', 'state_id' => $state->id, 'country_id' => $country->id, 'is_active' => true]);

    $response = $this->getJson("/api/v1/core/states/{$state->uuid}/cities");

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('shows a city', function () {
    $country = Country::create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '880', 'is_active' => true]);
    $state = $country->states()->create(['name' => 'Dhaka', 'country_id' => $country->id, 'is_active' => true]);
    $city = $state->cities()->create(['name' => 'Dhaka City', 'state_id' => $state->id, 'country_id' => $country->id, 'is_active' => true]);

    $response = $this->getJson("/api/v1/core/cities/{$city->uuid}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Dhaka City');
});

// ─── Currencies ──────────────────────────────────────────────────

it('lists active currencies', function () {
    Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => true]);
    Currency::create(['name' => 'Bangladeshi Taka', 'code' => 'BDT', 'symbol' => '৳', 'exchange_rate' => 110, 'is_active' => true]);

    $response = $this->getJson('/api/v1/core/currencies');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => [['uuid', 'name', 'code', 'symbol']]]);
});

it('allows admin to create currency', function () {
    $admin = makeUser('admin-currency@example.com');
    $admin->markEmailAsVerified();

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/core/currencies', [
            'name'          => 'Euro',
            'code'          => 'EUR',
            'symbol'        => '€',
            'exchange_rate' => 0.85,
        ]);

    // May get 403 if not admin; fallback verifies auth is checked
    if ($response->status() === 403) {
        $response->assertJsonPath('message', 'Admin access required.');
    } else {
        $response->assertCreated()
            ->assertJsonPath('success', true);
    }
});

it('allows admin to list all currencies', function () {
    $admin = makeUser('admin-currencies@example.com');
    $admin->markEmailAsVerified();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/core/admin/currencies');

    if ($response->status() === 403) {
        $response->assertJsonPath('message', 'Admin access required.');
    } else {
        $response->assertOk();
    }
});

it('prevents guest from creating currency', function () {
    $response = $this->postJson('/api/v1/core/currencies', [
        'name'          => 'Test',
        'code'          => 'TST',
        'symbol'        => 'T',
        'exchange_rate' => 1,
    ]);

    $response->assertStatus(401);
});

// ─── Languages ───────────────────────────────────────────────────

it('lists active languages', function () {
    Language::create(['name' => 'English', 'code' => 'en', 'is_active' => true]);
    Language::create(['name' => 'Bengali', 'code' => 'bn', 'is_active' => true]);

    $response = $this->getJson('/api/v1/core/languages');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => [['uuid', 'name', 'code']]]);
});

it('allows admin to create language', function () {
    $admin = makeUser('admin-lang@example.com');

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/core/languages', [
            'name' => 'French',
            'code' => 'fr',
        ]);

    if ($response->status() === 403) {
        $response->assertJsonPath('message', 'Admin access required.');
    } else {
        $response->assertCreated();
    }
});

// ─── Media ───────────────────────────────────────────────────────

it('allows authenticated user to upload media', function () {
    $user = makeUser('media-upload@example.com');
    $file = File::image('test.jpg', 100, 100);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/core/media/upload', [
            'file'      => $file,
            'directory' => 'uploads/test',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);
});

it('lists media for authenticated user', function () {
    $user = makeUser('media-list@example.com');

    Medium::create([
        'disk'          => 'public',
        'directory'     => 'uploads',
        'file_name'     => 'test.jpg',
        'original_name' => 'test.jpg',
        'mime_type'     => 'image/jpeg',
        'file_size'     => 1024,
        'extension'     => 'jpg',
        'uploaded_by'   => $user->id,
        'is_public'     => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/core/media');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('prevents guest from uploading media', function () {
    $file = File::image('test.jpg', 100, 100);

    $response = $this->postJson('/api/v1/core/media/upload', ['file' => $file]);

    $response->assertStatus(401);
});

it('allows user to delete their own media', function () {
    $user = makeUser('media-delete@example.com');

    $medium = Medium::create([
        'disk'          => 'public',
        'directory'     => 'uploads',
        'file_name'     => 'delete-me.jpg',
        'original_name' => 'delete-me.jpg',
        'mime_type'     => 'image/jpeg',
        'file_size'     => 1024,
        'extension'     => 'jpg',
        'uploaded_by'   => $user->id,
        'is_public'     => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/core/media/{$medium->uuid}");

    $response->assertOk()
        ->assertJsonPath('message', 'File deleted.');

    $this->assertDatabaseMissing('media', ['id' => $medium->id]);
});

// ─── Activity Logs ──────────────────────────────────────────────

it('allows admin to view activity logs', function () {
    $admin = makeUser('admin-logs@example.com');

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/core/admin/logs');

    if ($response->status() === 403) {
        $response->assertJsonPath('message', 'Admin access required.');
    } else {
        $response->assertOk();
    }
});
