<?php

namespace Modules\Cart\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Cart\Models\CompareList;
use Modules\Product\Models\Product;;

class CartDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'customer@example.com')->first();
        if (!$user) {
            $this->command->warn('No customer user found. Skipping cart seed.');
            return;
        }

        $products = Product::active()->take(3)->get();
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Skipping cart seed.');
            return;
        }

        // Seed a compare list if no products in it
        $compareList = CompareList::firstOrCreate(
            ['user_id' => $user->id],
            ['session_id' => null]
        );

        if ($compareList->items()->count() === 0) {
            foreach ($products as $product) {
                $compareList->items()->firstOrCreate(
                    ['product_id' => $product->id],
                    ['compare_list_id' => $compareList->id]
                );
            }
        }

        $this->command->info('Cart seed data created successfully.');
    }
}
