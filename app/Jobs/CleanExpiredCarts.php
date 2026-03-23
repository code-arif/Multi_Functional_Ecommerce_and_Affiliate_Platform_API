<?php

namespace App\Jobs;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanExpiredCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Delete expired guest carts (no user_id, past expiry)
        $deleted = Cart::whereNull('user_id')
                       ->where('expires_at', '<', now())
                       ->delete();

        // Delete empty authenticated carts older than 30 days
        $emptyDeleted = Cart::whereNotNull('user_id')
                            ->where('updated_at', '<', now()->subDays(30))
                            ->whereDoesntHave('items')
                            ->delete();

        Log::info('Cart cleanup complete', [
            'guest_carts_deleted' => $deleted,
            'empty_carts_deleted' => $emptyDeleted,
        ]);
    }

    public function queue(): string { return 'default'; }
}
