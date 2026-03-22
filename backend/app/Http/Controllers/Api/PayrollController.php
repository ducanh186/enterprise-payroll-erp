<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayrollService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PayrollService $payrollService
    ) {}

    public function periods(): JsonResponse
    {
        return $this->success($this->payrollService->getPeriods());
    }

    public function openPeriod(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'standard_working_days' => 'nullable|integer|min:1|max:31',
        ]);

        $result = $this->payrollService->openPeriod($request->all());

        return $this->created($result, 'Payroll period opened successfully.');
    }

    public function previewParameters(): JsonResponse
    {
        return $this->success($this->payrollService->getPreviewParameters());
    }

    public function previewRun(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'scope' => 'nullable|string|in:all,department',
            'department_id' => 'nullable|integer',
            'parameters' => 'nullable|array',
            'adjustments' => 'nullable|array',
        ]);

        $result = $this->payrollService->previewRun($request->all());

        return $this->success($result, 'Payroll preview generated.');
    }

    public function showRun(string $runId): JsonResponse
    {
        $result = $this->payrollService->getRun($runId);

        if (!$result) {
            return $this->notFound('Payroll run not found.');
        }

        return $this->success($result);
    }

    public function finalizeRun(string $runId): JsonResponse
    {
        $result = $this->payrollService->finalizeRun($runId);

        return $this->success($result, 'Payroll run finalized successfully.');
    }

    public function lockRun(string $runId): JsonResponse
    {
        $result = $this->payrollService->lockRun($runId);

        return $this->success($result, 'Payroll run locked successfully.');
    }

    public function payslips(Request $request): JsonResponse
    {
        $filters = $request->only(['month', 'year', 'employee_id', 'department_id', 'status', 'page', 'per_page']);
        $result = $this->payrollService->getPayslips($filters);

        return $this->paginated(
            $result['items'],
            $result['total'],
            $result['per_page'],
            $result['current_page']
        );
    }

    public function showPayslip(int $id): JsonResponse
    {
        $result = $this->payrollService->getPayslip($id);

        if (!$result) {
            return $this->notFound('Payslip not found.');
        }

        return $this->success($result);
    }

    public function payslipDetails(int $id): JsonResponse
    {
        $result = $this->payrollService->getPayslipDetails($id);

        if (!$result) {
            return $this->notFound('Payslip not found.');
        }

        return $this->success($result);
    }

    public function createAdjustment(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'type' => 'required|in:bonus,deduction,allowance',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'is_taxable' => 'nullable|boolean',
            'code' => 'nullable|string|max:50',
        ]);

        $result = $this->payrollService->createAdjustment($request->all());

        return $this->created($result, 'Payroll adjustment created.');
    }

    public function updateAdjustment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'type' => 'required|in:bonus,deduction,allowance',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'is_taxable' => 'nullable|boolean',
            'code' => 'nullable|string|max:50',
        ]);

        $result = $this->payrollService->updateAdjustment($id, $request->all());

        if (!$result) {
            return $this->notFound('Adjustment not found.');
        }

        return $this->success($result, 'Payroll adjustment updated.');
    }

    public function deleteAdjustment(int $id): JsonResponse
    {
        $this->payrollService->deleteAdjustment($id);

        return $this->success(null, 'Payroll adjustment deleted.');
    }
}
