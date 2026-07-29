<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\User;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorDocument;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────

function seedPermissions(): void
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    if (!Role::where('name', 'super-admin')->exists()) {
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'vendor', 'guard_name' => 'web']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $vendorPermissions = [
            'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.delete',
            'vendors.approve', 'vendors.manage', 'vendors.suspend',
        ];

        foreach ($vendorPermissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $superAdmin->givePermissionTo(Permission::all());
    }
}

function makeRoleUser(string $name, string $emailPrefix, string $role): User
{
    $user = User::create([
        'name'     => $name,
        'email'    => $emailPrefix . '-' . uniqid() . '@example.com',
        'phone'    => '+88017' . mt_rand(10000000, 99999999),
        'password' => bcrypt('password'),
        'status'   => 'active',
    ]);
    $user->assignRole($role);
    return $user;
}

function makeVendor(User $user, string $status = 'active', ?string $shopName = null): Vendor
{
    return Vendor::create([
        'user_id'         => $user->id,
        'shop_name'       => $shopName ?? 'Shop ' . uniqid(),
        'slug'            => 'shop-' . uniqid(),
        'email'           => $user->email,
        'phone'           => $user->phone,
        'description'     => 'Test shop description',
        'status'          => $status,
        'commission_rate' => 10,
        'commission_type' => 'percentage',
        'wallet_balance'  => 0,
        'total_earned'    => 0,
        'total_withdrawn' => 0,
        'approved_at'     => $status === 'active' ? now() : null,
    ]);
}

// ─── Public Routes ─────────────────────────────────────────────

it('lists active vendors for guests', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'vendor-list', 'vendor');
    makeVendor($vendorUser);

    $response = $this->getJson('/api/v1/vendors');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => [['id', 'shop_name', 'slug']]]);
});

it('shows a public vendor page by slug', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'vendor-show', 'vendor');
    $vendor = makeVendor($vendorUser);

    $response = $this->getJson("/api/v1/vendors/{$vendor->slug}");

    $response->assertOk()
        ->assertJsonPath('data.shop_name', $vendor->shop_name);
});

it('hides pending vendors from public', function () {
    seedPermissions();
    $user = makeRoleUser('Pending', 'pending', 'vendor');
    $pendingVendor = makeVendor($user, 'pending', 'Hidden Pending Shop');

    $response = $this->getJson("/api/v1/vendors/{$pendingVendor->slug}");
    $response->assertStatus(404);
});

it('only returns active vendors in public listing', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Active', 'active-list', 'vendor');
    makeVendor($vendorUser, 'active', 'Visible Shop');

    $pendingUser = makeRoleUser('PendingUser', 'pending-list', 'vendor');
    makeVendor($pendingUser, 'pending', 'Hidden Shop');

    $response = $this->getJson('/api/v1/vendors');
    $response->assertOk();

    $vendorNames = collect($response->json('data'))->pluck('shop_name')->toArray();
    expect($vendorNames)->toContain('Visible Shop');
    expect($vendorNames)->not->toContain('Hidden Shop');
});

// ─── Vendor Registration ───────────────────────────────────────

it('requires authentication to register as vendor', function () {
    $response = $this->postJson('/api/v1/vendor/register', ['shop_name' => 'New Shop']);
    $response->assertStatus(401);
});

it('allows customer to register as vendor', function () {
    seedPermissions();
    $customer = makeRoleUser('Customer', 'register', 'customer');

    $response = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/vendor/register', [
            'shop_name'     => 'My Awesome Shop',
            'email'         => 'shop-' . uniqid() . '@example.com',
            'phone'         => '+8801700000100',
            'description'   => 'Best shop ever',
            'business_type' => 'retail',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending');
});

it('prevents duplicate vendor registration', function () {
    seedPermissions();
    $customer = makeRoleUser('Customer', 'dup', 'customer');

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/vendor/register', ['shop_name' => 'First Shop']);

    $response = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/vendor/register', ['shop_name' => 'Second Shop']);

    $response->assertStatus(400);
});

it('validates shop name is required for registration', function () {
    seedPermissions();
    $customer = makeRoleUser('Customer', 'validation', 'customer');

    $response = $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/vendor/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['shop_name']);
});

// ─── Vendor Profile ────────────────────────────────────────────

it('allows vendor to view own profile', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'profile', 'vendor');
    $vendor = makeVendor($vendorUser);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->getJson('/api/v1/vendor/profile');

    $response->assertOk()
        ->assertJsonPath('data.shop_name', $vendor->shop_name);
});

it('returns 404 for non-vendor profile access', function () {
    seedPermissions();
    $customer = makeRoleUser('Customer', 'no-vendor', 'customer');

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/vendor/profile');

    $response->assertStatus(404);
});

it('allows vendor to update own profile', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'update', 'vendor');
    $vendor = makeVendor($vendorUser);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->putJson('/api/v1/vendor/profile', [
            'shop_name'    => 'Updated Shop Name',
            'description'  => 'Updated description',
            'business_type' => 'wholesale',
            'website'      => 'https://updatedshop.com',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.shop_name', 'Updated Shop Name');

    $this->assertDatabaseHas('vendors', [
        'id'          => $vendor->id,
        'shop_name'   => 'Updated Shop Name',
        'description' => 'Updated description',
    ]);

    $this->assertDatabaseHas('vendor_profiles', [
        'vendor_id'     => $vendor->id,
        'business_type' => 'wholesale',
    ]);
});

// ─── Vendor Documents ──────────────────────────────────────────

it('allows vendor to upload a document', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'doc', 'vendor');
    $vendor = makeVendor($vendorUser);
    $file = Illuminate\Http\Testing\File::image('trade_license.jpg', 100, 100);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/documents', [
            'type'            => 'trade_license',
            'document'        => $file,
            'document_number' => 'TR-12345',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('vendor_documents', [
        'vendor_id'       => $vendor->id,
        'type'            => 'trade_license',
        'document_number' => 'TR-12345',
        'status'          => 'pending',
    ]);
});

it('rejects invalid document type', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'doc-invalid', 'vendor');
    makeVendor($vendorUser);
    $file = Illuminate\Http\Testing\File::image('test.jpg', 100, 100);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/documents', [
            'type'     => 'invalid_type',
            'document' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

// ─── Admin Vendor Routes ───────────────────────────────────────

it('allows admin to list all vendors', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'admin-list', 'super-admin');

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/vendors');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('blocks non-admin from listing vendors', function () {
    seedPermissions();
    $customer = makeRoleUser('Customer', 'blocked', 'customer');

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/admin/vendors');

    $response->assertStatus(403);
});

it('shows pending vendors to admin', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'admin-pending', 'super-admin');
    $pendingUser = makeRoleUser('Pending', 'pending-admin', 'vendor');
    makeVendor($pendingUser, 'pending', 'Pending Approval Shop');

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/vendors/pending');

    $response->assertOk();
    $vendorNames = collect($response->json('data'))->pluck('shop_name')->toArray();
    expect($vendorNames)->toContain('Pending Approval Shop');
});

it('shows vendor details to admin', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'admin-detail', 'super-admin');
    $vendorUser = makeRoleUser('Vendor', 'detail', 'vendor');
    $vendor = makeVendor($vendorUser);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/vendors/{$vendor->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $vendor->id);
});

it('allows admin to approve a pending vendor', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'approve', 'super-admin');
    $pendingUser = makeRoleUser('Pending', 'to-approve', 'vendor');
    $pendingVendor = makeVendor($pendingUser, 'pending', 'Approve Shop');

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/vendors/{$pendingVendor->id}/approve");

    $response->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('vendors', [
        'id'     => $pendingVendor->id,
        'status' => 'active',
    ]);
});

it('allows admin to reject a pending vendor', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'reject', 'super-admin');
    $pendingUser = makeRoleUser('Pending', 'to-reject', 'vendor');
    $pendingVendor = makeVendor($pendingUser, 'pending', 'Reject Shop');

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/vendors/{$pendingVendor->id}/reject", [
            'reason' => 'Incomplete KYC documents',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $this->assertDatabaseHas('vendors', [
        'id'               => $pendingVendor->id,
        'status'           => 'rejected',
        'rejection_reason' => 'Incomplete KYC documents',
    ]);
});

it('requires reason when rejecting', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'reject-reason', 'super-admin');
    $pendingUser = makeRoleUser('Pending', 'no-reason', 'vendor');
    $pendingVendor = makeVendor($pendingUser, 'pending', 'No Reason');

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/vendors/{$pendingVendor->id}/reject", []);

    $response->assertStatus(422);
});

it('allows admin to suspend an active vendor', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'suspend', 'super-admin');
    $vendorUser = makeRoleUser('Vendor', 'to-suspend', 'vendor');
    $vendor = makeVendor($vendorUser);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/vendors/{$vendor->id}/suspend", [
            'reason' => 'Violation of terms',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'suspended');

    $this->assertDatabaseHas('vendors', [
        'id'     => $vendor->id,
        'status' => 'suspended',
    ]);
});

it('allows admin to verify a document', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'verify-doc', 'super-admin');
    $vendorUser = makeRoleUser('Vendor', 'doc-owner', 'vendor');
    $vendor = makeVendor($vendorUser);

    $document = VendorDocument::create([
        'vendor_id'       => $vendor->id,
        'type'            => 'trade_license',
        'document_path'   => 'vendors/documents/test.jpg',
        'document_number' => 'TR-999',
        'status'          => 'pending',
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/vendors/documents/{$document->id}/verify");

    $response->assertOk()
        ->assertJsonPath('data.status', 'verified');

    $this->assertDatabaseHas('vendor_documents', [
        'id'     => $document->id,
        'status' => 'verified',
    ]);
});

it('allows admin to reject a document', function () {
    seedPermissions();
    $admin = makeRoleUser('Admin', 'reject-doc', 'super-admin');
    $vendorUser = makeRoleUser('Vendor', 'doc-reject', 'vendor');
    $vendor = makeVendor($vendorUser);

    $document = VendorDocument::create([
        'vendor_id'       => $vendor->id,
        'type'            => 'nid',
        'document_path'   => 'vendors/documents/nid.jpg',
        'document_number' => 'NID-123',
        'status'          => 'pending',
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/admin/vendors/documents/{$document->id}/reject", [
            'reason' => 'Document is illegible',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $this->assertDatabaseHas('vendor_documents', [
        'id'               => $document->id,
        'status'           => 'rejected',
        'rejection_reason' => 'Document is illegible',
    ]);
});

// ─── Authorization ─────────────────────────────────────────────

it('requires auth for vendor profile', function () {
    $response = $this->getJson('/api/v1/vendor/profile');
    $response->assertStatus(401);
});

it('prevents vendor from self-approving via admin endpoint', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'self-approve', 'vendor');
    $pendingVendor = makeVendor($vendorUser, 'pending', 'Self Approve');

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->postJson("/api/v1/admin/vendors/{$pendingVendor->id}/approve");

    $response->assertStatus(403);
});

// ─── Wallet & Payouts ────────────────────────────────────────────

it('requires auth for wallet access', function () {
    $response = $this->getJson('/api/v1/vendor/wallet');
    $response->assertStatus(401);
});

it('shows wallet summary for vendor', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'wallet', 'vendor');
    makeVendor($vendorUser);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->getJson('/api/v1/vendor/wallet');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['balance', 'total_earned', 'total_withdrawn', 'available']]);
});

it('shows wallet transactions for vendor', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'txn', 'vendor');
    $vendor = makeVendor($vendorUser);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->getJson('/api/v1/vendor/wallet/transactions');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('shows wallet stats for vendor', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'stats', 'vendor');
    makeVendor($vendorUser);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->getJson('/api/v1/vendor/wallet/stats');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['current_balance', 'total_earned', 'total_withdrawn', 'available']]);
});

it('returns 404 for non-vendor wallet access', function () {
    seedPermissions();
    $customer = makeRoleUser('Customer', 'wallet-404', 'customer');

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/vendor/wallet');

    $response->assertStatus(404);
});

it('allows vendor to request a payout', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'payout-req', 'vendor');
    $vendor = makeVendor($vendorUser);
    // Give the vendor some wallet balance
    $vendor->update(['wallet_balance' => 5000]);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/wallet/payouts', [
            'amount' => 1000,
            'notes'  => 'Monthly payout',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.amount', 1000)
        ->assertJsonPath('data.status', 'pending');
});

it('rejects payout exceeding wallet balance', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'payout-exceed', 'vendor');
    $vendor = makeVendor($vendorUser);
    // Give some balance but request more
    $vendor->update(['wallet_balance' => 500]);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/wallet/payouts', [
            'amount' => 5000,
        ]);

    $response->assertStatus(400);
});

it('rejects payout below minimum amount', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'payout-min', 'vendor');
    $vendor = makeVendor($vendorUser);
    $vendor->update(['wallet_balance' => 5000]);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/wallet/payouts', [
            'amount' => 10, // Below minimum 100
        ]);

    $response->assertStatus(422);
});

it('lists vendor payout requests', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'payout-list', 'vendor');
    $vendor = makeVendor($vendorUser);
    $vendor->update(['wallet_balance' => 5000]);

    // Create a payout first
    $this->actingAs($vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/wallet/payouts', [
            'amount' => 1000,
        ]);

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->getJson('/api/v1/vendor/wallet/payouts');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

it('prevents payout for non-active vendor', function () {
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'payout-inactive', 'vendor');
    makeVendor($vendorUser, 'pending', 'Pending Payout Shop');

    $response = $this->actingAs($vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/wallet/payouts', [
            'amount' => 1000,
        ]);

    $response->assertStatus(403);
});

// ─── Vendor Passwordless Login (OTP-based) ──────────────────────

it('sends OTP for passwordless vendor login', function () {
    Mail::fake();
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'vendor-otp-send', 'vendor');
    makeVendor($vendorUser, 'active', 'OTP Shop');

    $response = $this->postJson('/api/v1/vendor/auth/otp/send', [
        'email' => $vendorUser->email,
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Verification code sent to your email.')
        ->assertJsonStructure(['data' => ['email']]);

    $this->assertDatabaseHas('otp_codes', [
        'user_id' => $vendorUser->id,
        'type'    => 'vendor_passwordless',
    ]);
});

it('rejects OTP send for non-vendor user', function () {
    seedPermissions();
    $customer = makeRoleUser('Customer', 'not-vendor', 'customer');

    $response = $this->postJson('/api/v1/vendor/auth/otp/send', [
        'email' => $customer->email,
    ]);

    $response->assertStatus(403);
});

it('rejects OTP send for non-existent email', function () {
    $response = $this->postJson('/api/v1/vendor/auth/otp/send', [
        'email' => 'nobody@example.com',
    ]);

    $response->assertStatus(422);
});

it('rejects OTP send for inactive vendor', function () {
    Mail::fake();
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'inactive-vendor', 'vendor');
    makeVendor($vendorUser, 'pending', 'Inactive Shop');

    $response = $this->postJson('/api/v1/vendor/auth/otp/send', [
        'email' => $vendorUser->email,
    ]);

    $response->assertStatus(403);
});

it('verifies OTP and logs in vendor', function () {
    Mail::fake();
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'vendor-otp-login', 'vendor');
    makeVendor($vendorUser, 'active', 'Login Shop');

    // Send OTP first
    $this->postJson('/api/v1/vendor/auth/otp/send', [
        'email' => $vendorUser->email,
    ]);

    // Get the OTP code from the database
    $otp = Modules\Auth\Models\OtpCode::where('user_id', $vendorUser->id)
        ->where('type', 'vendor_passwordless')
        ->first();

    $this->assertNotNull($otp);

    // Verify with the actual OTP code
    $response = $this->postJson('/api/v1/vendor/auth/otp/verify', [
        'email' => $vendorUser->email,
        'code'  => $otp->code,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonStructure(['data' => ['user', 'token', 'permissions']]);

    // OTP should be marked as used
    $this->assertNotNull($otp->fresh()->used_at);
});

it('rejects invalid OTP code during vendor login', function () {
    Mail::fake();
    seedPermissions();
    $vendorUser = makeRoleUser('Vendor', 'bad-otp', 'vendor');
    makeVendor($vendorUser, 'active', 'Bad OTP Shop');

    $response = $this->postJson('/api/v1/vendor/auth/otp/verify', [
        'email' => $vendorUser->email,
        'code'  => '000000',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Invalid or expired OTP code.');
});

it('rejects OTP verify for non-vendor user', function () {
    seedPermissions();
    $customer = makeRoleUser('Customer', 'not-vendor-verify', 'customer');

    $response = $this->postJson('/api/v1/vendor/auth/otp/verify', [
        'email' => $customer->email,
        'code'  => '123456',
    ]);

    $response->assertStatus(403);
});
