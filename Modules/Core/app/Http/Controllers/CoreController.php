<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CoreController
{
    use ApiResponse;

    public function health(): JsonResponse
    {
        return $this->successResponse([
            'status'    => 'ok',
            'version'   => config('app.version', '1.0'),
            'time'      => now()->toIso8601String(),
            'database'  => $this->checkDatabase(),
            'cache'     => $this->checkCache(),
        ]);
    }

    public function info(): JsonResponse
    {
        return $this->successResponse([
            'name'          => config('app.name'),
            'env'           => config('app.env'),
            'debug'         => config('app.debug'),
            'locale'        => app()->getLocale(),
            'timezone'      => config('app.timezone'),
            'currency'      => config('ecommerce.currency', 'BDT'),
            'currency_symbol' => config('ecommerce.currency_symbol', '৳'),
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            \Illuminate\Support\Facades\Cache::store('file')->get('health-check');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
