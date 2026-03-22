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

    public function show(int $id): JsonResponse
    {
        $contract = $this->employeeService->getContract($id);

        if (!$contract) {
            return $this->notFound('Contract not found.');
        }

        return $this->success($contract);
    }
}
