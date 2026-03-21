<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@ecoshop.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('Admin@12345'),
                'status'   => 'active',
                'email_verified_at' => now(),
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
