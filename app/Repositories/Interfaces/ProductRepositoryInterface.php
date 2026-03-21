<?php

namespace App\Repositories\Interfaces;

use App\Models\Product;

interface ProductRepositoryInterface
{
    public function getFilteredProducts(array $filters);
    public function findBySlug(string $slug): Product;
    public function findById(int $id): Product;
    public function create(array $data): Product;
    public function update(int $id, array $data): Product;
    public function delete(int $id): bool;
}
