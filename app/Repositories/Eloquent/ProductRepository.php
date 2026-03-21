<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Traits\HasSlug;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private Product $model) {}

    public function findBySlug(string $slug): Product
    {
        return $this->model
            ->with(['images', 'variants', 'attributes.values', 'category', 'brand', 'reviews.user'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function findById(int $id): Product
    {
        return $this->model->with(['images', 'variants', 'attributes.values', 'category', 'brand'])->findOrFail($id);
    }

    public function getFilteredProducts(array $filters)
    {
        return $this->model
            ->with(['images', 'category', 'brand'])
            ->active()
            ->when(!empty($filters['category']), fn($q) =>
                $q->whereHas('category', fn($cq) =>
                    $cq->where('slug', $filters['category'])
                       ->orWhere('id', fn($sq) =>
                           $sq->select('id')->from('categories')
                              ->where('slug', $filters['category'])
                       )
                )
            )
            ->when(!empty($filters['brand']), fn($q) =>
                $q->whereHas('brand', fn($bq) => $bq->where('slug', $filters['brand'])))
            ->when(isset($filters['min_price']), fn($q) =>
                $q->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn($q) =>
                $q->where('price', '<=', $filters['max_price']))
            ->when(!empty($filters['rating']), fn($q) =>
                $q->where('average_rating', '>=', $filters['rating']))
            ->when(!empty($filters['search']), fn($q) =>
                $q->where(fn($sq) =>
                    $sq->where('name', 'like', "%{$filters['search']}%")
                       ->orWhere('short_description', 'like', "%{$filters['search']}%")
                ))
            ->when(!empty($filters['featured']), fn($q) => $q->where('is_featured', true))
            ->when(!empty($filters['new']),      fn($q) => $q->where('is_new', true))
            ->when(!empty($filters['sort']), function($q) use ($filters) {
                match($filters['sort']) {
                    'price_asc'  => $q->orderBy('price', 'asc'),
                    'price_desc' => $q->orderBy('price', 'desc'),
                    'newest'     => $q->orderBy('created_at', 'desc'),
                    'popularity' => $q->orderBy('total_sold', 'desc'),
                    'rating'     => $q->orderBy('average_rating', 'desc'),
                    default      => $q->orderBy('created_at', 'desc'),
                };
            }, fn($q) => $q->orderBy('created_at', 'desc'))
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->findById($id);
        $product->update($data);
        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->destroy($id);
    }
}
