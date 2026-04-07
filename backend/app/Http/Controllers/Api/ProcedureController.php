<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProcedureService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProcedureController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProcedureService $procedureService
    ) {}

    /**
     * GET /api/procedures
     * List all active stored procedures.
     */
    public function index(): JsonResponse
    {
        return $this->success($this->procedureService->listProcedures());
    }

    /**
     * GET /api/procedures/{code}/meta
     * Get full metadata for a single procedure (params + columns).
     */
    public function meta(string $code): JsonResponse
    {
        return $this->success($this->procedureService->getMeta($code));
    }

    /**
     * POST /api/procedures/{code}/execute
     * Execute a stored procedure with provided parameters.
     */
    public function execute(Request $request, string $code): JsonResponse
    {
        try {
            $result = $this->procedureService->execute(
                $code,
                $request->except(['_token']),
                $request->user()?->id,
                $request->ip()
            );

            return $this->success($result);
        } catch (ValidationException $e) {
            return $this->error(
                'Tham số không hợp lệ.',
                422,
                $e->errors()
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
