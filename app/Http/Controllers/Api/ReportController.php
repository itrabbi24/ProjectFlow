<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function dashboard(): JsonResponse
    {
        // View reports permission required for analytics dashboard access, or standard user permission
        $stats = $this->reportService->getDashboardStats();
        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function projectProfit(Request $request): JsonResponse
    {
        Gate::authorize('view_reports');

        $filters = $request->only(['project_id', 'client_id', 'status']);
        $report = $this->reportService->getProjectProfitReport($filters);

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }

    public function expense(Request $request): JsonResponse
    {
        Gate::authorize('view_reports');

        $filters = $request->only(['start_date', 'end_date', 'project_id', 'category']);
        $report = $this->reportService->getExpenseReport($filters);

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }

    public function income(Request $request): JsonResponse
    {
        Gate::authorize('view_reports');

        $filters = $request->only(['start_date', 'end_date', 'project_id', 'client_id', 'category']);
        $report = $this->reportService->getIncomeReport($filters);

        return response()->json([
            'status' => 'success',
            'data' => $report
        ]);
    }

}
