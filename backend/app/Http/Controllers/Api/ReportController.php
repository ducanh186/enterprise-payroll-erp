<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function templates(): JsonResponse
    {
        return $this->success($this->reportService->getTemplates());
    }

    public function preview(Request $request, string $code): JsonResponse
    {
        $result = $this->reportService->previewReport($code, $request->all());

        return $this->success($result);
    }

    public function export(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'format' => 'nullable|in:xlsx,pdf,csv',
        ]);

        $result = $this->reportService->exportReport($code, $request->all());

        return $this->success($result, 'Report exported successfully.');
    }
}
