<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EmployeeService $employeeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'per_page']);
        $result = $this->employeeService->getContracts($filters);

        return $this->paginated(
            $result['items'],
            $result['total'],
            $result['per_page'],
            $result['current_page']
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->contractRules());
        $contract = $this->employeeService->createContract($data);

        return $this->created($contract, 'Contract created.');
    }

    public function show(int $id): JsonResponse
    {
        $contract = $this->employeeService->getContract($id);

        if (!$contract) {
            return $this->notFound('Contract not found.');
        }

        return $this->success($contract);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate($this->contractRules(true));
        $contract = $this->employeeService->updateContract($id, $data);

        return $contract ? $this->success($contract, 'Contract updated.') : $this->notFound('Contract not found.');
    }

    public function suspend(int $id): JsonResponse
    {
        $contract = $this->employeeService->suspendContract($id);

        return $contract ? $this->success($contract, 'Contract suspended.') : $this->notFound('Contract not found.');
    }

    private function contractRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $optional = $partial ? 'sometimes' : 'nullable';

        return [
            'employee_id' => [$optional, 'integer', 'exists:employees,id'],
            'employee_code' => [$required, 'string', 'max:20'],
            'contract_no' => [$required, 'string', 'max:30'],
            'contract_type_id' => [$optional, 'integer', 'exists:contract_types,id'],
            'contract_type_code' => [$optional, 'string', 'max:20'],
            'payroll_type_id' => [$optional, 'integer', 'exists:payroll_types,id'],
            'salary_level_id' => [$optional, 'nullable', 'integer', 'exists:salary_levels,id'],
            'start_date' => [$required, 'date'],
            'end_date' => [$optional, 'nullable', 'date'],
            'sign_date' => [$optional, 'nullable', 'date'],
            'base_salary' => [$optional, 'numeric', 'min:0'],
            'probation_rate' => [$optional, 'numeric', 'min:0', 'max:100'],
            'status' => [$optional, 'string', 'max:20'],
        ];
    }
}
