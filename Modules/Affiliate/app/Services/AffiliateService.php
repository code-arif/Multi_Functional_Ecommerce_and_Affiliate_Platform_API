<?php

namespace Modules\Affiliate\Services;

use Modules\Affiliate\Models\AffiliateProduct;
use Modules\Affiliate\Models\AffiliateClick;

class AffiliateService
{
    public function getProducts(array $filters = [])
    {
        $query = AffiliateProduct::active();

        if (!empty($filters['featured'])) {
            $query->featured();
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('sort_order')->paginate(20);
    }

    public function getProductBySlug(string $slug): ?AffiliateProduct
    {
        return AffiliateProduct::active()->where('slug', $slug)->firstOrFail();
    }

    public function trackClick(AffiliateProduct $product, string $ip, ?string $userAgent = null, ?string $referrer = null): void
    {
        $product->clicks()->create([
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'referrer'   => $referrer,
        ]);
    }
}
