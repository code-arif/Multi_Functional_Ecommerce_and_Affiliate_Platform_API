<?php

namespace App\Services;

use App\Models\AffiliateProduct;
use App\Models\AffiliateClick;

class AffiliateService
{
    public function trackClick(AffiliateProduct $product, ?int $userId, string $ip, string $userAgent): void
    {
        AffiliateClick::create([
            'affiliate_product_id' => $product->id,
            'user_id'              => $userId,
            'ip_address'           => $ip,
            'user_agent'           => $userAgent,
        ]);

        $product->increment('click_count');
    }

    public function getProducts(array $filters)
    {
        return AffiliateProduct::with('category')
            ->active()
            ->when(!empty($filters['category']), fn($q) =>
                $q->whereHas('category', fn($q) =>
                    $q->where('slug', $filters['category'])))
            ->when(!empty($filters['platform']), fn($q) =>
                $q->where('source_platform', $filters['platform']))
            ->when(!empty($filters['search']), fn($q) =>
                $q->where('title', 'like', "%{$filters['search']}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }
}
