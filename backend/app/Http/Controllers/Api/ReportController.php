<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function download(string $fileName): BinaryFileResponse
    {
        abort_unless(preg_match('/^[A-Z0-9_\\-]+_[0-9A-Za-z_\\-]+\\.(xlsx|csv)$/', $fileName), 404);

        $path = storage_path('app/public/reports/' . $fileName);
        abort_unless(is_file($path), 404);

        return response()->download($path);
    }
}
