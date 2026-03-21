<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(): JsonResponse
    {
        $stats = $this->reportService->getDashboardStats();
        return $this->successResponse($stats, 'Dashboard data fetched.');
    }
}
