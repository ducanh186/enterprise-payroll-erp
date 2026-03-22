<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployee($id);

        if (!$employee) {
            return $this->notFound('Employee not found.');
        }

        return $this->success($employee);
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
}
