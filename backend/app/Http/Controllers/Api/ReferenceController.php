<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferenceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ReferenceService $referenceService
    ) {}

    public function shifts(): JsonResponse
    {
        return $this->success($this->referenceService->getShifts());
    }

    public function holidays(): JsonResponse
    {
        return $this->success($this->referenceService->getHolidays());
    }

    public function contractTypes(): JsonResponse
    {
        return $this->success($this->referenceService->getContractTypes());
    }

    public function storeContractType(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('contract_types', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'duration_months' => ['nullable', 'integer', 'min:0'],
            'is_probationary' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        return $this->created($this->referenceService->createContractType($data), 'Contract type created.');
    }

    public function updateContractType(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('contract_types', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:100'],
            'duration_months' => ['nullable', 'integer', 'min:0'],
            'is_probationary' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $record = $this->referenceService->updateContractType($id, $data);

        return $record ? $this->success($record, 'Contract type updated.') : $this->notFound('Contract type not found.');
    }

    public function suspendContractType(int $id): JsonResponse
    {
        $record = $this->referenceService->suspendContractType($id);

        return $record ? $this->success($record, 'Contract type suspended.') : $this->notFound('Contract type not found.');
    }

    public function payrollTypes(): JsonResponse
    {
        return $this->success($this->referenceService->getPayrollTypes());
    }

    public function payrollParameters(): JsonResponse
    {
        return $this->success($this->referenceService->getPayrollParameters());
    }

    /**
     * Fujimart D20PayrollParameter flat table with 2 tabs.
     * GET /api/reference/d20-payroll-parameters?tab=value|salary
     */
    public function d20PayrollParameters(Request $request): JsonResponse
    {
        $tab = $request->input('tab', 'value');

        $query = \App\Models\D20PayrollParameter::query()->where('is_active', true);
        if ($tab === 'salary') {
            $query->salaryTab();
        } else {
            $query->valueTab();
        }

        return $this->success($query->orderBy('parameter')->get());
    }

    public function storePayrollParameter(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('payroll_parameters', 'code')],
            'name' => ['required', 'string', 'max:200'],
            'value' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date'],
            'type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        return $this->created($this->referenceService->createPayrollParameter($data), 'Payroll parameter created.');
    }

    public function updatePayrollParameter(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('payroll_parameters', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:200'],
            'value' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['nullable', 'date'],
            'type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $record = $this->referenceService->updatePayrollParameter($id, $data);

        return $record ? $this->success($record, 'Payroll parameter updated.') : $this->notFound('Payroll parameter not found.');
    }

    public function suspendPayrollParameter(int $id): JsonResponse
    {
        $record = $this->referenceService->suspendPayrollParameter($id);

        return $record ? $this->success($record, 'Payroll parameter suspended.') : $this->notFound('Payroll parameter not found.');
    }

    public function lateEarlyRules(): JsonResponse
    {
        return $this->success($this->referenceService->getLateEarlyRules());
    }

    public function departments(): JsonResponse
    {
        return $this->success($this->referenceService->getDepartments());
    }

    public function salaryLevels(): JsonResponse
    {
        return $this->success($this->referenceService->getSalaryLevels());
    }

    public function salaryScales(): JsonResponse
    {
        return $this->success($this->referenceService->getSalaryScales());
    }

    public function storeSalaryScale(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('payroll_types', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:256'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        return $this->created($this->referenceService->createSalaryScale($data), 'Salary scale created.');
    }

    public function updateSalaryScale(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('payroll_types', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:256'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $record = $this->referenceService->updateSalaryScale($id, $data);

        return $record ? $this->success($record, 'Salary scale updated.') : $this->notFound('Salary scale not found.');
    }

    public function suspendSalaryScale(int $id): JsonResponse
    {
        $record = $this->referenceService->suspendSalaryScale($id);

        return $record ? $this->success($record, 'Salary scale suspended.') : $this->notFound('Salary scale not found.');
    }

    public function storeSalaryGrade(Request $request, int $scaleId): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'level_no' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $record = $this->referenceService->createSalaryGrade($scaleId, $data);

        return $record ? $this->created($record, 'Salary grade created.') : $this->notFound('Salary scale not found.');
    }

    public function updateSalaryGrade(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:20'],
            'level_no' => ['sometimes', 'integer', 'min:1'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $record = $this->referenceService->updateSalaryGrade($id, $data);

        return $record ? $this->success($record, 'Salary grade updated.') : $this->notFound('Salary grade not found.');
    }

    public function suspendSalaryGrade(int $id): JsonResponse
    {
        $record = $this->referenceService->suspendSalaryGrade($id);

        return $record ? $this->success($record, 'Salary grade suspended.') : $this->notFound('Salary grade not found.');
    }

    public function salaryGradeDetails(int $id): JsonResponse
    {
        return $this->success($this->referenceService->getSalaryGradeDetails($id));
    }

    public function storeSalaryGradeDetail(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'salary_type' => ['required', 'string', 'max:128'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:512'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $record = $this->referenceService->createSalaryGradeDetail($id, $data);

        return $record ? $this->created($record, 'Salary grade detail created.') : $this->notFound('Salary grade not found.');
    }

    public function updateSalaryGradeDetail(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'salary_type' => ['sometimes', 'string', 'max:128'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:512'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $record = $this->referenceService->updateSalaryGradeDetail($id, $data);

        return $record ? $this->success($record, 'Salary grade detail updated.') : $this->notFound('Salary grade detail not found.');
    }

    public function allowances(): JsonResponse
    {
        return $this->success($this->referenceService->getAllowances());
    }

    public function storeAllowance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('allowance_types', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['nullable', 'boolean'],
            'is_insurance_base' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        return $this->created($this->referenceService->createAllowance($data), 'Allowance created.');
    }

    public function updateAllowance(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('allowance_types', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:100'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['nullable', 'boolean'],
            'is_insurance_base' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $record = $this->referenceService->updateAllowance($id, $data);

        return $record ? $this->success($record, 'Allowance updated.') : $this->notFound('Allowance not found.');
    }

    public function suspendAllowance(int $id): JsonResponse
    {
        $record = $this->referenceService->suspendAllowance($id);

        return $record ? $this->success($record, 'Allowance suspended.') : $this->notFound('Allowance not found.');
    }
}
