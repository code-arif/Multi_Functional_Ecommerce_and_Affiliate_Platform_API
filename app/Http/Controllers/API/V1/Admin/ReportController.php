<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;
    
    public function __construct(private ReportService $reportService) {}

    public function sales(Request $request): JsonResponse
    {
        $data = $this->reportService->getSalesReport(
            $request->period ?? 'daily',
            $request->from,
            $request->to
        );
        return $this->successResponse($data);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $data = $this->reportService->getTopProducts($request->limit ?? 10, $request->from, $request->to);
        return $this->successResponse($data);
    }

    public function ordersByStatus(): JsonResponse
    {
        return $this->successResponse($this->reportService->getOrdersByStatus());
    }

    public function customerGrowth(Request $request): JsonResponse
    {
        return $this->successResponse($this->reportService->getCustomerGrowth($request->months ?? 12));
    }
}
