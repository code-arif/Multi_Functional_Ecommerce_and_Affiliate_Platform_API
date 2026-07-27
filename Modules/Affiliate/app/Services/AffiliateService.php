<?php

namespace Modules\Affiliate\Services;

use Modules\Affiliate\Models\AffiliateProduct;
use Modules\Affiliate\Models\AffiliateClick;
use Modules\Affiliate\Models\AffiliateConversion;
use Modules\Affiliate\Models\AffiliateEarning;
use Modules\Auth\Models\User;
use Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffiliateService
{
    // ─── Product Queries ───────────────────────────────────────────

    public function getProducts(array $filters = [])
    {
        $query = AffiliateProduct::active()->ordered();

        if (!empty($filters['featured'])) {
            $query->featured();
        }
        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }
        if (!empty($filters['platform'])) {
            $query->byPlatform($filters['platform']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query->withCount('clicks')->paginate($filters['per_page'] ?? 20);
    }

    public function getProductBySlug(string $slug): AffiliateProduct
    {
        return AffiliateProduct::active()->where('slug', $slug)->firstOrFail();
    }

    // ─── Click Tracking ────────────────────────────────────────────

    public function trackClick(AffiliateProduct $product, string $ip, ?string $userAgent = null, ?string $referrer = null, ?User $user = null): void
    {
        DB::transaction(function () use ($product, $ip, $userAgent, $referrer, $user) {
            $product->clicks()->create([
                'user_id'    => $user?->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'referrer'   => $referrer,
            ]);

            $product->increment('click_count');
        });
    }

    // ─── Conversion Tracking ───────────────────────────────────────

    /**
     * Record a conversion when an affiliate-referred purchase completes.
     */
    public function recordConversion(AffiliateProduct $product, User $user, Order $order): AffiliateConversion
    {
        $commissionAmount = $this->calculateCommission($product, $order->total);

        $conversion = AffiliateConversion::create([
            'affiliate_product_id' => $product->id,
            'user_id'             => $user->id,
            'order_id'            => $order->id,
            'order_amount'        => $order->total,
            'commission_amount'   => $commissionAmount,
            'status'              => 'pending',
            'converted_at'        => now(),
        ]);

        // Create pending earning record
        AffiliateEarning::create([
            'user_id'             => $user->id,
            'affiliate_product_id' => $product->id,
            'order_id'            => $order->id,
            'amount'              => $commissionAmount,
            'type'                => 'commission',
            'status'              => 'pending',
            'notes'               => "Commission for {$product->title} via Order #{$order->order_number}",
        ]);

        Log::info('Affiliate conversion recorded', [
            'product_id'       => $product->id,
            'user_id'          => $user->id,
            'commission'       => $commissionAmount,
            'order_id'         => $order->id,
        ]);

        return $conversion;
    }

    /**
     * Approve a conversion and mark earnings as available.
     */
    public function approveConversion(AffiliateConversion $conversion): AffiliateConversion
    {
        DB::transaction(function () use ($conversion) {
            $conversion->update(['status' => 'approved']);

            // Mark corresponding earning as available (with cooling period)
            AffiliateEarning::where('affiliate_product_id', $conversion->affiliate_product_id)
                ->where('order_id', $conversion->order_id)
                ->where('user_id', $conversion->user_id)
                ->update([
                    'status'       => 'available',
                    'available_at' => now()->addDays(30), // 30-day cooling period
                ]);
        });

        return $conversion->fresh();
    }

    public function rejectConversion(AffiliateConversion $conversion): AffiliateConversion
    {
        DB::transaction(function () use ($conversion) {
            $conversion->update(['status' => 'rejected']);

            AffiliateEarning::where('affiliate_product_id', $conversion->affiliate_product_id)
                ->where('order_id', $conversion->order_id)
                ->where('user_id', $conversion->user_id)
                ->update(['status' => 'cancelled']);
        });

        return $conversion->fresh();
    }

    // ─── Earnings & Payouts ────────────────────────────────────────

    /**
     * Mark earnings as paid.
     */
    public function markAsPaid(array $earningIds): int
    {
        return AffiliateEarning::whereIn('id', $earningIds)
            ->where('status', 'available')
            ->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);
    }

    /**
     * Get total available earnings for a user.
     */
    public function getAvailableBalance(int $userId): float
    {
        return (float) AffiliateEarning::byUser($userId)
            ->available()
            ->sum('amount');
    }

    /**
     * Get total lifetime earnings for a user.
     */
    public function getLifetimeEarnings(int $userId): float
    {
        return (float) AffiliateEarning::byUser($userId)
            ->whereIn('status', ['available', 'paid'])
            ->sum('amount');
    }

    // ─── Dashboard ─────────────────────────────────────────────────

    /**
     * Get affiliate dashboard summary for a user.
     */
    public function getDashboard(User $user): array
    {
        $clicks = AffiliateClick::where('user_id', $user->id)->count();
        $conversions = AffiliateConversion::where('user_id', $user->id)->count();
        $approvedConversions = AffiliateConversion::where('user_id', $user->id)->approved()->count();

        $earnings = AffiliateEarning::byUser($user->id);

        return [
            'total_clicks'        => $clicks,
            'total_conversions'   => $conversions,
            'approved_conversions'  => $approvedConversions,
            'conversion_rate'     => $clicks > 0 ? round(($approvedConversions / $clicks) * 100, 2) : 0,
            'available_balance'   => $this->getAvailableBalance($user->id),
            'lifetime_earnings'   => $this->getLifetimeEarnings($user->id),
            'pending_earnings'    => (float) $earnings->pending()->sum('amount'),
            'total_paid'          => (float) $earnings->paid()->sum('amount'),
        ];
    }

    /**
     * Get earning history for a user.
     */
    public function getEarningsHistory(int $userId, array $filters = [])
    {
        $query = AffiliateEarning::byUser($userId)->with('product');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    // ─── Analytics (Admin) ─────────────────────────────────────────

    public function getAnalytics(): array
    {
        $totalProducts = AffiliateProduct::count();
        $activeProducts = AffiliateProduct::active()->count();
        $totalClicks = AffiliateClick::count();
        $totalConversions = AffiliateConversion::count();
        $approvedConversions = AffiliateConversion::approved()->count();

        $totalCommissions = AffiliateEarning::whereIn('status', ['available', 'paid'])->sum('amount');
        $totalPaid = AffiliateEarning::paid()->sum('amount');

        // Top products by clicks
        $topProducts = AffiliateProduct::withCount('clicks')
            ->active()
            ->orderByDesc('clicks_count')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'            => $p->id,
                'title'         => $p->title,
                'clicks'        => (int) $p->clicks_count,
                'conversions'   => $p->conversions()->count(),
                'conversion_rate' => $p->clicks_count > 0
                    ? round(($p->conversions()->count() / $p->clicks_count) * 100, 2)
                    : 0,
            ]);

        // Earnings by platform
        $earningsByPlatform = AffiliateProduct::selectRaw(
            'source_platform, COUNT(DISTINCT affiliate_products.id) as product_count'
        )->groupBy('source_platform')->get();

        return [
            'total_products'    => $totalProducts,
            'active_products'   => $activeProducts,
            'total_clicks'      => $totalClicks,
            'total_conversions' => $totalConversions,
            'approved_conversions' => $approvedConversions,
            'overall_conversion_rate' => $totalClicks > 0
                ? round(($approvedConversions / $totalClicks) * 100, 2) : 0,
            'total_commissions' => (float) $totalCommissions,
            'total_paid'        => (float) $totalPaid,
            'top_products'      => $topProducts,
            'platforms'         => $earningsByPlatform,
        ];
    }

    // ─── Commission Calculation ────────────────────────────────────

    public function calculateCommission(AffiliateProduct $product, float $orderTotal): float
    {
        return $product->commission_type === 'percentage'
            ? round($orderTotal * ($product->commission_value / 100), 2)
            : min($product->commission_value, $orderTotal);
    }
}
