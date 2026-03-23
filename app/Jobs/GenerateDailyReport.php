<?php

namespace App\Jobs;

use App\Services\Cache\CacheService;
use App\Services\ReportService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDailyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function handle(ReportService $report, CacheService $cache): void
    {
        try {
            $today = now()->toDateString();

            // Pre-generate and cache today's report
            $dailySales = $report->getSalesReport('daily', $today, $today);

            // Cache for 24 hours
            $cache->remember("report:daily:{$today}", 86400, fn() => $dailySales);

            // Flush dashboard stats so next load gets fresh data
            $cache->flushReports();

            Log::info('Daily report generated', [
                'date'         => $today,
                'total_orders' => collect($dailySales)->sum('total_orders'),
                'revenue'      => collect($dailySales)->sum('revenue'),
            ]);
        } catch (Exception $e) {
            Log::error('Daily report generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function queue(): string { return 'reports'; }
}
