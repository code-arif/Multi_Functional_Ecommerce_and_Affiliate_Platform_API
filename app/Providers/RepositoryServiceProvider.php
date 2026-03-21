<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Eloquent\CartRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        // $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        // $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        // $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
    }
}
