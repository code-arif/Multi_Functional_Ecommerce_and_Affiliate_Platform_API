<?php

use App\Models\User;

if (!function_exists('makeAdminUser')) {
    function makeAdminUser(): User
    {
        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin-' . uniqid() . '@example.com',
            'phone'    => '+88017' . mt_rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status'   => 'active',
        ]);
        $user->assignRole('super-admin');
        return $user;
    }
}

if (!function_exists('makeCustomerUser')) {
    function makeCustomerUser(): User
    {
        $user = User::create([
            'name'     => 'Customer',
            'email'    => 'customer-' . uniqid() . '@example.com',
            'phone'    => '+88017' . mt_rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'status'   => 'active',
        ]);
        $user->assignRole('customer');
        return $user;
    }
}
