<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\User;
use Modules\Auth\Models\OtpCode;
use Modules\Auth\Models\Device;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────

function seedRoles(): void
{
    app()[Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    if (!Role::where('name', 'super-admin')->exists()) {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'vendor', 'guard_name' => 'web']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        Permission::findOrCreate('admin', 'web');
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo('admin');
        $superAdmin = Role::findByName('super-admin');
        $superAdmin->givePermissionTo(Permission::all());
    }
}

if (!function_exists('makeUser')) {
    function makeUser(string $email, string $status = 'active', ?string $name = null): User
    {
    return User::create([
        'name'     => $name ?? 'Test User',
        'email'    => $email,
        'phone'    => '+88017' . mt_rand(10000000, 99999999),
        'password' => Hash::make('password'),
        'status'   => $status,
    ]);
    }
}

// ─── Registration ────────────────────────────────────────────────

it('registers a new user successfully', function () {
    Mail::fake();

    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Registration successful.')
        ->assertJsonStructure(['data' => ['user' => ['uuid', 'name', 'email'], 'token']]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'name'  => 'John Doe',
    ]);
});

it('rejects registration with missing fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('rejects registration with duplicate email', function () {
    makeUser('dup@example.com');

    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'Duplicate',
        'email'                 => 'dup@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects registration with mismatched passwords', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name'                  => 'No Match',
        'email'                 => 'nomatch@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'different',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

// ─── Login ───────────────────────────────────────────────────────

it('logs in with valid credentials', function () {
    makeUser('login-test@example.com');

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'login-test@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonStructure(['data' => ['user', 'token']]);
});

it('rejects login with invalid password', function () {
    makeUser('wrong-pass@example.com');

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'wrong-pass@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
});

it('rejects login for banned user', function () {
    makeUser('banned@example.com', 'banned');

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'banned@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(403);
});

it('rejects login with non-existent email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'ghost@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(401);
});

// ─── Admin Login ─────────────────────────────────────────────────

it('logs in admin with valid credentials', function () {
    seedRoles();
    $admin = makeUser('admin-login@example.com');
    $admin->assignRole('super-admin');

    $response = $this->postJson('/api/v1/auth/admin/login', [
        'email'    => 'admin-login@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['user', 'token', 'permissions']]);
});

it('blocks non-admin from admin login', function () {
    seedRoles();
    $customer = makeUser('customer-login@example.com');
    $customer->assignRole('customer');

    $response = $this->postJson('/api/v1/auth/admin/login', [
        'email'    => 'customer-login@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(403);
});

// ─── Logout ──────────────────────────────────────────────────────

it('logs out current token', function () {
    $user = makeUser('logout-test@example.com');
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/v1/auth/logout');

    $response->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('logs out from all devices', function () {
    $user = makeUser('logout-all@example.com');
    $user->createToken('device-1');
    $user->createToken('device-2');
    $token = $user->createToken('current')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/v1/auth/logout-all');

    $response->assertOk()
        ->assertJsonPath('message', 'Logged out from all devices.');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('requires authentication for logout', function () {
    $response = $this->postJson('/api/v1/auth/logout');
    $response->assertStatus(401);
});

// ─── Profile ─────────────────────────────────────────────────────

it('returns authenticated user profile', function () {
    $user = makeUser('profile-test@example.com');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['uuid', 'name', 'email']]);
});

it('updates user profile', function () {
    $user = makeUser('update-profile@example.com');

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/auth/profile', [
            'name' => 'New Name',
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Profile updated.');

    $this->assertDatabaseHas('users', [
        'id'   => $user->id,
        'name' => 'New Name',
    ]);
});

it('changes password when current password is correct', function () {
    $user = makeUser('pw-change@example.com');

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/auth/profile', [
            'current_password'      => 'password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response->assertOk();
    $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
});

it('rejects password change with wrong current password', function () {
    $user = makeUser('pw-wrong@example.com');

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/auth/profile', [
            'current_password'      => 'wrong-pass',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Current password is incorrect.');
});

it('updates avatar', function () {
    $user = makeUser('avatar@example.com');
    $file = Illuminate\Http\Testing\File::image('avatar.jpg', 100, 100);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/avatar', [
            'avatar' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Avatar updated.')
        ->assertJsonStructure(['data' => ['avatar_url']]);
});

it('rejects invalid avatar file type', function () {
    $user = makeUser('avatar-bad@example.com');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/avatar', [
            'avatar' => 'not-a-file',
        ]);

    $response->assertStatus(422);
});

// ─── OTP ─────────────────────────────────────────────────────────

it('sends OTP for password reset', function () {
    Mail::fake();
    $user = makeUser('otp-send@example.com');

    $response = $this->postJson('/api/v1/auth/otp/send', [
        'type'  => 'password_reset',
        'email' => 'otp-send@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'OTP sent successfully.');

    $this->assertDatabaseHas('otp_codes', [
        'user_id' => $user->id,
        'type'    => 'password_reset',
    ]);
});

it('verifies valid OTP code', function () {
    $user = makeUser('otp-verify@example.com');

    $otp = OtpCode::create([
        'user_id'    => $user->id,
        'code'       => '123456',
        'type'       => 'password_reset',
        'channel'    => 'email',
        'destination'=> $user->email,
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'code'  => '123456',
        'type'  => 'password_reset',
        'email' => 'otp-verify@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.verified', true);

    $this->assertNotNull($otp->fresh()->used_at);
});

it('rejects invalid OTP code', function () {
    makeUser('otp-bad@example.com');

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'code'  => '000000',
        'type'  => 'password_reset',
        'email' => 'otp-bad@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Invalid or expired OTP code.');
});

it('rejects expired OTP code', function () {
    $user = makeUser('otp-expired@example.com');

    OtpCode::create([
        'user_id'    => $user->id,
        'code'       => '999999',
        'type'       => 'login',
        'channel'    => 'email',
        'destination'=> $user->email,
        'expires_at' => now()->subMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'code'  => '999999',
        'type'  => 'login',
        'email' => 'otp-expired@example.com',
    ]);

    $response->assertStatus(422);
});

it('validates email exists for OTP send', function () {
    $response = $this->postJson('/api/v1/auth/otp/send', [
        'type'  => 'password_reset',
        'email' => 'nobody@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// ─── Password Reset ──────────────────────────────────────────────

it('sends password reset OTP', function () {
    Mail::fake();
    makeUser('forgot@example.com');

    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'forgot@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'If that email is registered, you will receive a password reset OTP.');
});

it('resets password with valid OTP', function () {
    $user = makeUser('reset-valid@example.com');

    OtpCode::create([
        'user_id'    => $user->id,
        'code'       => '654321',
        'type'       => 'password_reset',
        'channel'    => 'email',
        'destination'=> $user->email,
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson('/api/v1/auth/password/reset', [
        'email'                 => 'reset-valid@example.com',
        'code'                  => '654321',
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Password reset successful. Please login with your new password.');

    $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
});

it('rejects password reset with invalid OTP', function () {
    makeUser('reset-bad@example.com');

    $response = $this->postJson('/api/v1/auth/password/reset', [
        'email'                 => 'reset-bad@example.com',
        'code'                  => '000000',
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(422);
});

it('does not reveal if email exists for password forgot', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'If that email is registered, you will receive a password reset OTP.');
});

// ─── Email Verification ──────────────────────────────────────────

it('sends email verification OTP', function () {
    Mail::fake();
    $user = User::create([
        'name'              => 'Verify User',
        'email'             => 'email-verify-send@example.com',
        'phone'             => '+8801700000100',
        'password'          => Hash::make('password'),
        'email_verified_at' => null,
        'status'            => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/verify/send');

    $response->assertOk()
        ->assertJsonPath('message', 'Verification OTP sent to your email.');
});

it('verifies email with valid OTP', function () {
    $user = User::create([
        'name'              => 'Email Confirm',
        'email'             => 'email-confirm@example.com',
        'phone'             => '+8801700000101',
        'password'          => Hash::make('password'),
        'email_verified_at' => null,
        'status'            => 'active',
    ]);

    OtpCode::create([
        'user_id'    => $user->id,
        'code'       => '111111',
        'type'       => 'email_verification',
        'channel'    => 'email',
        'destination'=> $user->email,
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/verify', [
            'code' => '111111',
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Email verified successfully.');

    $this->assertNotNull($user->fresh()->email_verified_at);
});

it('says already verified if email is verified', function () {
    $user = User::create([
        'name'              => 'Already Verified',
        'email'             => 'already-verified@example.com',
        'phone'             => '+8801700000102',
        'password'          => Hash::make('password'),
        'email_verified_at' => now(),
        'status'            => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/email/verify/send');

    $response->assertOk()
        ->assertJsonPath('message', 'Email already verified.');
});

it('returns email verification status', function () {
    $user = User::create([
        'name'              => 'Email Status',
        'email'             => 'email-status@example.com',
        'phone'             => '+8801700000103',
        'password'          => Hash::make('password'),
        'email_verified_at' => now(),
        'status'            => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/email/status');

    $response->assertOk()
        ->assertJsonPath('data.email', 'email-status@example.com')
        ->assertJsonPath('data.is_verified', true);
});

// ─── Devices ─────────────────────────────────────────────────────

it('lists authenticated user devices', function () {
    $user = makeUser('devices-list@example.com');
    Device::create([
        'user_id'     => $user->id,
        'device_name' => 'Test Device',
        'device_type' => 'desktop',
        'ip_address'  => '127.0.0.1',
        'user_agent'  => 'Test/1.0',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/devices');

    $response->assertOk();
});

it('revokes a specific device', function () {
    $user = makeUser('devices-revoke@example.com');
    $device = Device::create([
        'user_id'     => $user->id,
        'device_name' => 'Revoke Me',
        'device_type' => 'mobile',
        'ip_address'  => '127.0.0.1',
        'user_agent'  => 'Test/1.0',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/auth/devices/{$device->uuid}");

    $response->assertOk();
    $this->assertDatabaseMissing('devices', ['id' => $device->id]);
});

it('returns 404 when revoking non-existent device', function () {
    $user = makeUser('devices-404@example.com');

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/auth/devices/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
});

it('marks a device as trusted', function () {
    $user = makeUser('devices-trust@example.com');
    $device = Device::create([
        'user_id'     => $user->id,
        'device_name' => 'Trust Me',
        'device_type' => 'desktop',
        'ip_address'  => '127.0.0.1',
        'user_agent'  => 'Test/1.0',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/auth/devices/{$device->uuid}/trust");

    $response->assertOk()
        ->assertJsonPath('message', 'Device marked as trusted.');

    $this->assertTrue((bool) $device->fresh()->is_trusted);
});

it('revokes all devices', function () {
    $user = makeUser('devices-revoke-all@example.com');
    Device::create(['user_id' => $user->id, 'device_name' => 'D1', 'device_type' => 'mobile', 'ip_address' => '1.1.1.1', 'user_agent' => 'A']);
    Device::create(['user_id' => $user->id, 'device_name' => 'D2', 'device_type' => 'desktop', 'ip_address' => '2.2.2.2', 'user_agent' => 'B']);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/auth/devices');

    $response->assertOk();
    $this->assertDatabaseMissing('devices', ['user_id' => $user->id]);
});

// ─── Addresses ───────────────────────────────────────────────────

it('creates an address', function () {
    $user = makeUser('addr-create@example.com');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/addresses', [
            'recipient_name'      => 'Home',
            'phone'          => '+8801700000001',
            'address_line1' => '123 Main St',
            'city'           => 'Dhaka',
            'country'        => 'Bangladesh',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'city'    => 'Dhaka',
    ]);
});

it('lists user addresses', function () {
    $user = makeUser('addr-list@example.com');
    $user->addresses()->create([
        'recipient_name'      => 'Home',
        'phone'          => '+8801700000001',
        'address_line1' => '456 Oak Ave',
        'city'           => 'Chittagong',
        'country'        => 'Bangladesh',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/addresses');

    $response->assertOk();
});

it('shows a specific address', function () {
    $user = makeUser('addr-show@example.com');
    $address = $user->addresses()->create([
        'recipient_name'      => 'Office',
        'phone'          => '+8801700000002',
        'address_line1' => '789 Work Rd',
        'city'           => 'Dhaka',
        'country'        => 'Bangladesh',
        'is_default'     => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/addresses/{$address->uuid}");

    $response->assertOk()
        ->assertJsonPath('data.uuid', $address->uuid)
        ->assertJsonPath('data.city', 'Dhaka');
});

it('updates an address', function () {
    $user = makeUser('addr-update@example.com');
    $address = $user->addresses()->create([
        'recipient_name'      => 'Old Name',
        'phone'          => '+8801700000003',
        'address_line1' => 'Old St',
        'city'           => 'Old City',
        'country'        => 'Bangladesh',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/addresses/{$address->uuid}", [
            'recipient_name' => 'Updated Name',
            'city'      => 'New City',
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Address updated.');

    $this->assertDatabaseHas('addresses', [
        'id'        => $address->id,
        'recipient_name' => 'Updated Name',
        'city'      => 'New City',
    ]);
});

it('deletes an address', function () {
    $user = makeUser('addr-delete@example.com');
    $address = $user->addresses()->create([
        'recipient_name'      => 'Delete Me',
        'phone'          => '+8801700000004',
        'address_line1' => 'Delete St',
        'city'           => 'Delete City',
        'country'        => 'Bangladesh',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/addresses/{$address->uuid}");

    $response->assertOk()
        ->assertJsonPath('message', 'Address deleted.');

    $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
});

it('prevents accessing another users address', function () {
    $user1 = makeUser('addr-user1@example.com');
    $user2 = makeUser('addr-user2@example.com');

    $address = $user1->addresses()->create([
        'recipient_name'      => 'Private',
        'phone'          => '+8801700000005',
        'address_line1' => 'Private St',
        'city'           => 'Private City',
        'country'        => 'Bangladesh',
    ]);

    $response = $this->actingAs($user2, 'sanctum')
        ->getJson("/api/v1/addresses/{$address->uuid}");

    $response->assertStatus(404);
});

// ─── Authorization ──────────────────────────────────────────────

it('requires authentication for profile', function () {
    $response = $this->getJson('/api/v1/auth/me');
    $response->assertStatus(401);
});

it('requires authentication for addresses', function () {
    $response = $this->getJson('/api/v1/addresses');
    $response->assertStatus(401);
});

it('requires authentication for email verification', function () {
    $response = $this->postJson('/api/v1/auth/email/verify/send');
    $response->assertStatus(401);
});

it('requires authentication for devices', function () {
    $response = $this->getJson('/api/v1/auth/devices');
    $response->assertStatus(401);
});

// ─── Passwordless Admin Login (OTP-based) ──────────────────────

it('sends OTP for passwordless admin login', function () {
    Mail::fake();
    seedRoles();
    $admin = makeUser('admin-otp-send@example.com');
    $admin->assignRole('super-admin');

    $response = $this->postJson('/api/v1/auth/admin/otp/send', [
        'email' => 'admin-otp-send@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Verification code sent to your email.')
        ->assertJsonStructure(['data' => ['email']]);

    $this->assertDatabaseHas('otp_codes', [
        'user_id' => $admin->id,
        'type'    => 'admin_passwordless',
    ]);
});

it('rejects OTP send for non-admin user', function () {
    seedRoles();
    $customer = makeUser('customer-otp@example.com');
    $customer->assignRole('customer');

    $response = $this->postJson('/api/v1/auth/admin/otp/send', [
        'email' => 'customer-otp@example.com',
    ]);

    $response->assertStatus(403);
});

it('rejects OTP send for non-existent email', function () {
    $response = $this->postJson('/api/v1/auth/admin/otp/send', [
        'email' => 'nobody@example.com',
    ]);

    $response->assertStatus(422);
});

it('verifies OTP and logs in admin', function () {
    Mail::fake();
    seedRoles();
    $admin = makeUser('admin-otp-verify@example.com');
    $admin->assignRole('super-admin');

    // Send OTP first
    $this->postJson('/api/v1/auth/admin/otp/send', [
        'email' => 'admin-otp-verify@example.com',
    ]);

    // Get the OTP code from the database
    $otp = Modules\Auth\Models\OtpCode::where('user_id', $admin->id)
        ->where('type', 'admin_passwordless')
        ->first();

    $this->assertNotNull($otp);

    // Verify with the actual OTP code
    $response = $this->postJson('/api/v1/auth/admin/otp/verify', [
        'email' => 'admin-otp-verify@example.com',
        'code'  => $otp->code,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonStructure(['data' => ['user', 'token', 'permissions']]);

    // OTP should be marked as used
    $this->assertNotNull($otp->fresh()->used_at);
});

it('rejects invalid OTP code during admin login', function () {
    Mail::fake();
    seedRoles();
    $admin = makeUser('admin-bad-otp@example.com');
    $admin->assignRole('super-admin');

    $response = $this->postJson('/api/v1/auth/admin/otp/verify', [
        'email' => 'admin-bad-otp@example.com',
        'code'  => '000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Invalid or expired OTP code.');
});

it('rejects OTP verify for non-admin user', function () {
    seedRoles();
    $customer = makeUser('customer-verify@example.com');
    $customer->assignRole('customer');

    $response = $this->postJson('/api/v1/auth/admin/otp/verify', [
        'email' => 'customer-verify@example.com',
        'code'  => '123456',
    ]);

    $response->assertStatus(403);
});

it('rejects OTP verify for banned admin', function () {
    Mail::fake();
    seedRoles();
    $admin = makeUser('banned-admin@example.com', 'banned');
    $admin->assignRole('super-admin');

    $response = $this->postJson('/api/v1/auth/admin/otp/verify', [
        'email' => 'banned-admin@example.com',
        'code'  => '123456',
    ]);

    $response->assertStatus(403);
});

it('banned user cannot access protected routes', function () {
    $banned = makeUser('banned-access@example.com', 'banned');

    $response = $this->actingAs($banned, 'sanctum')
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(403);
});
