<?php

namespace Modules\Promotions\Services;

use Modules\Promotions\Models\Promotion;
use Modules\Promotions\Models\PromotionUsage;
use Modules\Catalog\Models\Product;
use Modules\Cart\Models\CartItem;
use Modules\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    // ─── Query Active Promotions ───────────────────────────────────

    public function getActivePromotions(array $filters = [])
    {
        $query = Promotion::active()->sortByDisplay();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['product_id'])) {
            $product = Product::find($filters['product_id']);
            if ($product) {
                $query->where(function ($q) use ($product) {
                    $q->where('applies_to', 'all')
                      ->orWhere(function ($q) use ($product) {
                          $q->where('applies_to', 'products')
                            ->whereJsonContains('product_ids', $product->id);
                      })
                      ->orWhere(function ($q) use ($product) {
                          $q->where('applies_to', 'categories')
                            ->whereJsonContains('category_ids', $product->category_id);
                      })
                      ->orWhere(function ($q) use ($product) {
                          $q->where('applies_to', 'vendors')
                            ->whereJsonContains('vendor_ids', $product->vendor_id);
                      });
                });
            }
        }

        return $query->get();
    }

    public function getActiveFlashSales()
    {
        return Promotion::flashSales()->sortByDisplay()->get();
    }

    public function getUpcomingPromotions()
    {
        return Promotion::upcoming()->sortByDisplay()->get();
    }

    // ─── Validation ────────────────────────────────────────────────

    /**
     * Validate a promotion for a user and cart context.
     */
    public function validatePromotion(Promotion $promotion, User $user, array $cartContext = []): array
    {
        // Check active
        if (!$promotion->is_currently_active) {
            return ['valid' => false, 'message' => 'This promotion is not active.'];
        }

        // Check usage limit
        if ($promotion->hasReachedUsageLimit()) {
            return ['valid' => false, 'message' => 'This promotion has reached its usage limit.'];
        }

        // Check per-user limit
        if ($promotion->userHasExceededLimit($user->id)) {
            return ['valid' => false, 'message' => 'You have already used this promotion.'];
        }

        return ['valid' => true, 'message' => 'Promotion is valid.'];
    }

    // ─── Application Strategies ────────────────────────────────────

    /**
     * Calculate discount for an item based on an active promotion.
     */
    public function applyPromotionToItem(Promotion $promotion, CartItem $item, int $quantity = 1): array
    {
        if (!$promotion->appliesToProduct($item->product)) {
            return [
                'applied'  => false,
                'discount' => 0,
                'label'    => null,
            ];
        }

        $price = $item->product->current_price;
        $totalBeforeDiscount = $price * $quantity;

        return match ($promotion->type) {
            'flash_sale'   => $this->applyFlashSale($promotion, $price, $quantity, $totalBeforeDiscount),
            'seasonal'     => $this->applyFlashSale($promotion, $price, $quantity, $totalBeforeDiscount),
            'free_shipping' => [
                'applied'  => true,
                'discount' => 0, // Shipping discount handled separately
                'label'    => 'Free Shipping',
            ],
            default => [
                'applied'  => false,
                'discount' => 0,
                'label'    => null,
            ],
        };
    }

    private function applyFlashSale(Promotion $promotion, float $price, int $quantity, float $total): array
    {
        $unitDiscount = $promotion->discount_type === 'percentage'
            ? $price * ($promotion->discount_value / 100)
            : $promotion->discount_value;

        $unitDiscount = min($unitDiscount, $price); // Don't exceed item price

        if ($promotion->maximum_discount) {
            $unitDiscount = min($unitDiscount, $promotion->maximum_discount / $quantity);
        }

        $totalDiscount = round($unitDiscount * $quantity, 2);

        return [
            'applied'  => true,
            'discount' => $totalDiscount,
            'label'    => $promotion->discount_label,
        ];
    }

    /**
     * Calculate buy-x-get-y discount.
     * For every min_quantity purchased, free_quantity items are free (cheapest ones).
     */
    public function applyBuyXGetY(array $items, Promotion $promotion): array
    {
        $minQty = $promotion->min_quantity;
        $freeQty = $promotion->free_quantity;
        $totalDiscount = 0;
        $appliedItems = [];

        // Sort items by price ascending (for "discount_on: cheapest")
        usort($items, fn($a, $b) => $a['price'] <=> $b['price']);

        $totalQty = array_sum(array_column($items, 'quantity'));

        // Calculate how many free items the user qualifies for
        $qualifyingSets = intdiv($totalQty, $minQty + $freeQty);
        $freeItemsCount = $qualifyingSets * $freeQty;

        // Apply discounts starting from cheapest items
        $remainingFree = $freeItemsCount;
        foreach ($items as $item) {
            if ($remainingFree <= 0) break;

            $qtyToFree = min($item['quantity'], $remainingFree);
            $discount = $qtyToFree * $item['price'];
            $totalDiscount += $discount;
            $remainingFree -= $qtyToFree;

            $appliedItems[] = [
                'product_id'   => $item['product_id'],
                'free_quantity' => $qtyToFree,
                'discount'     => $discount,
            ];
        }

        return [
            'applied'       => $totalDiscount > 0,
            'discount'      => round($totalDiscount, 2),
            'free_count'    => $freeItemsCount,
            'applied_items' => $appliedItems,
            'label'         => $promotion->discount_label,
        ];
    }

    /**
     * Calculate tiered discount for a set of items with quantities.
     */
    public function applyTieredDiscount(array $items, Promotion $promotion): array
    {
        $totalQty = array_sum(array_column($items, 'quantity'));
        $tierRate = $promotion->getTieredDiscountValue($totalQty);

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $discount = $promotion->discount_type === 'percentage'
            ? $subtotal * ($tierRate / 100)
            : min($tierRate, $subtotal);

        if ($promotion->maximum_discount) {
            $discount = min($discount, $promotion->maximum_discount);
        }

        return [
            'applied'       => $discount > 0,
            'discount'      => round($discount, 2),
            'tier_quantity'  => $totalQty,
            'tier_rate'      => $tierRate,
            'label'          => "{$promotion->discount_label} ({$tierRate}%)",
        ];
    }

    // ─── Record Usage ──────────────────────────────────────────────

    public function recordUsage(Promotion $promotion, User $user, int $orderId, float $discountAmount): void
    {
        DB::transaction(function () use ($promotion, $user, $orderId, $discountAmount) {
            PromotionUsage::create([
                'promotion_id'    => $promotion->id,
                'user_id'         => $user->id,
                'order_id'        => $orderId,
                'discount_amount' => $discountAmount,
            ]);

            $promotion->increment('used_count');
        });
    }

    // ─── Analytics ─────────────────────────────────────────────────

    public function getAnalytics(array $filters = []): array
    {
        $query = Promotion::query();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $promotions = $query->get();

        $totalPromotions = $promotions->count();
        $activePromotions = $promotions->filter(fn($p) => $p->is_currently_active)->count();
        $totalUsage = PromotionUsage::count();
        $totalDiscountGiven = PromotionUsage::sum('discount_amount');

        $usageByType = [];
        foreach (['flash_sale', 'buy_x_get_y', 'tiered_discount', 'seasonal', 'free_shipping'] as $type) {
            $typePromotions = $promotions->where('type', $type);
            if ($typePromotions->isEmpty()) continue;

            $typeIds = $typePromotions->pluck('id');
            $usageByType[$type] = [
                'count'   => $typePromotions->count(),
                'active'  => $typePromotions->filter(fn($p) => $p->is_currently_active)->count(),
                'usages'  => PromotionUsage::whereIn('promotion_id', $typeIds)->count(),
                'discount' => (float) PromotionUsage::whereIn('promotion_id', $typeIds)->sum('discount_amount'),
            ];
        }

        $topPromotions = PromotionUsage::selectRaw('promotion_id, COUNT(*) as usage_count, SUM(discount_amount) as total_discount')
            ->groupBy('promotion_id')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get()
            ->map(fn($usage) => [
                'promotion'       => optional(Promotion::find($usage->promotion_id))->name,
                'type'            => optional(Promotion::find($usage->promotion_id))->type,
                'usage_count'     => (int) $usage->usage_count,
                'total_discount'  => (float) $usage->total_discount,
            ]);

        return [
            'total_promotions'    => $totalPromotions,
            'active_promotions'   => $activePromotions,
            'total_usage'         => $totalUsage,
            'total_discount_given' => $totalDiscountGiven,
            'usage_by_type'       => $usageByType,
            'top_promotions'      => $topPromotions,
        ];
    }

    // ─── CRUD ──────────────────────────────────────────────────────

    public function createPromotion(array $data): Promotion
    {
        if (isset($data['tiers']) && is_array($data['tiers'])) {
            $data['tiers'] = $data['tiers'];
        }

        return Promotion::create($data);
    }

    public function updatePromotion(Promotion $promotion, array $data): Promotion
    {
        $promotion->update($data);
        return $promotion->fresh();
    }

    public function toggleActive(Promotion $promotion): Promotion
    {
        $promotion->update(['is_active' => !$promotion->is_active]);
        return $promotion->fresh();
    }

    public function deletePromotion(Promotion $promotion): void
    {
        $promotion->delete();
    }
}
