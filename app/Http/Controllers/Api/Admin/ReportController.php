<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RevenueReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Báo cáo doanh thu theo Rạp/Ngày/Phim & Revenue Chart
     * Hỗ trợ Context-Aware Data Scoping theo rạp/khu vực.
     */
    public function revenue(RevenueReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->validated();

        // Also merge unvalidated fallback inputs if needed (e.g. from_date/to_date)
        if (!isset($filters['from_date']) && $request->has('from_date')) {
            $filters['from_date'] = $request->input('from_date');
        }
        if (!isset($filters['to_date']) && $request->has('to_date')) {
            $filters['to_date'] = $request->input('to_date');
        }

        $reportData = $this->reportService->getRevenueReport($user, $filters);

        return response()->json([
            'success' => true,
            'data'    => $reportData,
        ]);
    }
}
