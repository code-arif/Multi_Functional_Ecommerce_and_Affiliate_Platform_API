<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class RecalculateProductRatingsCommand extends Command
{
    protected $signature   = 'products:recalculate-ratings {--product_id= : Recalculate a single product}';
    protected $description = 'Recalculate average ratings and review counts for all products';

    public function handle(): int
    {
        $productId = $this->option('product_id');

        $query = Product::with('reviews');
        if ($productId) {
            $query->where('id', $productId);
        }

        $products = $query->get();
        $bar      = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $approvedReviews = $product->reviews()->where('status', 'approved');
            $avg   = $approvedReviews->avg('rating') ?? 0;
            $total = $approvedReviews->count();

            $product->update([
                'average_rating' => round($avg, 2),
                'total_reviews'  => $total,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Ratings recalculated for {$products->count()} products.");

        return self::SUCCESS;
    }
}
