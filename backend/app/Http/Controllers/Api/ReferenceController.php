<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

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

    public function payrollTypes(): JsonResponse
    {
        return $this->success($this->referenceService->getPayrollTypes());
    }

    public function payrollParameters(): JsonResponse
    {
        return $this->success($this->referenceService->getPayrollParameters());
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

    public function allowances(): JsonResponse
    {
        return $this->success($this->referenceService->getAllowances());
    }
}
