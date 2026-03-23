<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanExpiredCartsCommand extends Command
{
    protected $signature   = 'carts:clean {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Remove expired guest carts and empty old carts';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Expired guest carts
        $expiredGuestQuery = Cart::whereNull('user_id')->where('expires_at', '<', now());
        $expiredCount      = $expiredGuestQuery->count();

        // Old empty user carts (30 days inactive with no items)
        $emptyUserQuery = Cart::whereNotNull('user_id')
                              ->where('updated_at', '<', now()->subDays(30))
                              ->whereDoesntHave('items');
        $emptyCount     = $emptyUserQuery->count();

        $this->table(
            ['Type', 'Count'],
            [
                ['Expired guest carts', $expiredCount],
                ['Empty user carts (30+ days)', $emptyCount],
            ]
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] No records deleted.');
            return self::SUCCESS;
        }

        if ($this->confirm("Delete {$expiredCount} guest carts and {$emptyCount} empty carts?", true)) {
            $expiredGuestQuery->delete();
            $emptyUserQuery->delete();

            $this->info("Cleaned {$expiredCount} guest carts and {$emptyCount} empty carts.");
            Log::info('Cart cleanup via artisan command', [
                'guest_carts' => $expiredCount,
                'empty_carts' => $emptyCount,
            ]);
        }

        return self::SUCCESS;
    }
}
