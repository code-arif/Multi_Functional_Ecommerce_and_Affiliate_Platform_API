<?php

use App\Jobs\CleanExpiredCarts;
use App\Jobs\GenerateDailyReport;
use App\Jobs\WarmCacheJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->withSchedule(function (Schedule $schedule) {
        // ─── Reports ─────────────────────────────────────────────
        $schedule->job(new GenerateDailyReport(), 'reports')
            ->dailyAt('00:05')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('GenerateDailyReport schedule failed');
            });

        // ─── Cart Cleanup ────────────────────────────────────────
        $schedule->job(new CleanExpiredCarts(), 'default')
            ->dailyAt('00:15')
            ->withoutOverlapping();

        // ─── Sitemap ─────────────────────────────────────────────
        $schedule->command('sitemap:generate')
            ->dailyAt('00:30')
            ->withoutOverlapping();

        // ─── Cache Warm ───────────────────────────────────────────
        $schedule->job(new WarmCacheJob(), 'default')
            ->hourly()
            ->withoutOverlapping();

        // ─── Rating Recalculation ─────────────────────────────────
        $schedule->command('products:recalculate-ratings')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->withoutOverlapping();

        // ─── Queue Monitor ────────────────────────────────────────
        $schedule->command('queue:prune-failed --hours=168') // 7 days
            ->weekly();

        // ─── Log cleanup ─────────────────────────────────────────
        $schedule->command('log:clear')
            ->monthly();
    })
    ->create();
