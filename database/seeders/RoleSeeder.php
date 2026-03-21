<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin',      'display_name' => 'Administrator'],
            ['name' => 'customer',   'display_name' => 'Customer'],
            ['name' => 'moderator',  'display_name' => 'Moderator'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
