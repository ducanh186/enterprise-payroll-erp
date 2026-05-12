<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EmployeeService $employeeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['keyword', 'department_id', 'active_status', 'page', 'per_page']);
        $result = $this->employeeService->getEmployees($filters);

        return $this->paginated(
            $result['items'],
            $result['total'],
            $result['per_page'],
            $result['current_page']
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->employeeRules());
        $employee = $this->employeeService->createEmployee($data);

        return $this->created($employee, 'Employee created.');
    }

    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployee($id);

        if (!$employee) {
            return $this->notFound('Employee not found.');
        }

        return $this->success($employee);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate($this->employeeRules($id, true));
        $employee = $this->employeeService->updateEmployee($id, $data);

        if (!$employee) {
            return $this->notFound('Employee not found.');
        }

        return $this->success($employee, 'Employee updated.');
    }

    public function suspend(int $id): JsonResponse
    {
        $employee = $this->employeeService->suspendEmployee($id);

        if (!$employee) {
            return $this->notFound('Employee not found.');
        }

        return $this->success($employee, 'Employee suspended.');
    }

    public function activeContract(int $id): JsonResponse
    {
        $contract = $this->employeeService->getActiveContract($id);

        if (!$contract) {
            return $this->notFound('No active contract found for this employee.');
        }

        return $this->success($contract);
    }

    public function dependents(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployee($id);

        if (!$employee) {
            return $this->notFound('Employee not found.');
        }

        $dependents = $this->employeeService->getDependents($id);

        return $this->success($dependents);
    }

    public function storeDependent(Request $request, int $id): JsonResponse
    {
        $data = $request->validate($this->dependentRules());
        $dependent = $this->employeeService->createDependent($id, $data);

        if (!$dependent) {
            return $this->notFound('Employee not found.');
        }

        return $this->created($dependent, 'Dependent created.');
    }

    public function updateDependent(Request $request, int $id, int $dependentId): JsonResponse
    {
        $data = $request->validate($this->dependentRules(true));
        $dependent = $this->employeeService->updateDependent($id, $dependentId, $data);

        if (!$dependent) {
            return $this->notFound('Dependent not found.');
        }

        return $this->success($dependent, 'Dependent updated.');
    }

    private function employeeRules(?int $id = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $optional = $partial ? 'sometimes' : 'nullable';

        return [
            'employee_code' => [$required, 'string', 'max:20', Rule::unique('employees', 'employee_code')->ignore($id)],
            'full_name' => [$required, 'string', 'max:100'],
            'gender' => [$optional, 'nullable', 'string', 'max:10'],
            'birth_date' => [$optional, 'nullable', 'date'],
            'date_of_birth' => [$optional, 'nullable', 'date'],
            'dob' => [$optional, 'nullable', 'date'],
            'id_card_no' => [$optional, 'nullable', 'string', 'max:20'],
            'identity_number' => [$optional, 'nullable', 'string', 'max:20'],
            'national_id' => [$optional, 'nullable', 'string', 'max:20', Rule::unique('employees', 'national_id')->ignore($id)],
            'email' => [$optional, 'nullable', 'email', 'max:100'],
            'mobile' => [$optional, 'nullable', 'string', 'max:20'],
            'phone' => [$optional, 'nullable', 'string', 'max:20'],
            'tax_code' => [$optional, 'nullable', 'string', 'max:20'],
            'bank_account_no' => [$optional, 'nullable', 'string', 'max:30'],
            'bank_account' => [$optional, 'nullable', 'string', 'max:30'],
            'bank_name' => [$optional, 'nullable', 'string', 'max:100'],
            'department_id' => [$optional, 'nullable', 'integer', 'exists:departments,id'],
            'position_id' => [$optional, 'nullable', 'integer', 'exists:positions,id'],
            'hire_date' => [$optional, 'nullable', 'date'],
            'join_date' => [$optional, 'nullable', 'date'],
            'start_date' => [$optional, 'nullable', 'date'],
            'resign_date' => [$optional, 'nullable', 'date'],
            'status' => [$optional, 'nullable', 'string', 'max:20'],
            'employment_status' => [$optional, 'nullable', 'string', 'max:20'],
        ];
    }

    private function dependentRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $optional = $partial ? 'sometimes' : 'nullable';

        return [
            'full_name' => [$required, 'string', 'max:100'],
            'relationship' => [$required, 'string', 'max:50'],
            'date_of_birth' => [$optional, 'nullable', 'date'],
            'birth_date' => [$optional, 'nullable', 'date'],
            'dob' => [$optional, 'nullable', 'date'],
            'identity_number' => [$optional, 'nullable', 'string', 'max:20'],
            'id_card_no' => [$optional, 'nullable', 'string', 'max:20'],
            'national_id' => [$optional, 'nullable', 'string', 'max:20'],
            'tax_deduction_from' => [$optional, 'nullable', 'date'],
            'tax_deduction_to' => [$optional, 'nullable', 'date'],
            'tax_reduction_from' => [$optional, 'nullable', 'date'],
            'tax_reduction_to' => [$optional, 'nullable', 'date'],
        ];
    }
}
