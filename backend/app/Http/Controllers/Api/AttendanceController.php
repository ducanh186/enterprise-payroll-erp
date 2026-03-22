<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    public function checkinLogs(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_from', 'date_to', 'employee_id', 'machine_number',
            'is_valid', 'page', 'per_page',
        ]);
        $result = $this->attendanceService->getCheckinLogs($filters);

        return $this->paginated(
            $result['items'],
            $result['total'],
            $result['per_page'],
            $result['current_page']
        );
    }

    public function manualCheckin(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'check_time' => 'required|date',
            'check_type' => 'required|in:in,out',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->attendanceService->createManualCheckin($request->all());

        return $this->created($result, 'Manual check-in created successfully.');
    }

    public function daily(Request $request): JsonResponse
    {
        $filters = $request->only(['date', 'department_id']);
        $result = $this->attendanceService->getDailyAttendance($filters);

        return $this->success($result);
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        $filters = $request->only(['month', 'year', 'department_id', 'employee_id']);
        $result = $this->attendanceService->getMonthlySummary($filters);

        return $this->success($result);
    }

    public function recalculate(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $result = $this->attendanceService->recalculate($request->all());

        return $this->success($result, 'Recalculation completed.');
    }

    public function requestsIndex(Request $request): JsonResponse
    {
        $result = $this->attendanceService->getRequests($request->all());

        return $this->success($result);
    }

    public function requestsStore(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'request_type' => 'required|in:late_excuse,missing_checkout,leave,overtime,early_leave',
            'request_date' => 'required|date',
            'reason' => 'required|string|max:1000',
        ]);

        $result = $this->attendanceService->createRequest($request->all());

        return $this->created($result, 'Attendance request created successfully.');
    }

    public function requestsShow(int $id): JsonResponse
    {
        $result = $this->attendanceService->getRequest($id);

        if (!$result) {
            return $this->notFound('Attendance request not found.');
        }

        return $this->success($result);
    }

    public function requestsApprove(Request $request, int $id): JsonResponse
    {
        $result = $this->attendanceService->approveRequest($id, $request->all());

        if (!$result) {
            return $this->notFound('Attendance request not found.');
        }

        return $this->success($result, 'Request approved successfully.');
    }

    public function requestsReject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'note' => 'required|string|max:500',
        ]);

        $result = $this->attendanceService->rejectRequest($id, $request->all());

        if (!$result) {
            return $this->notFound('Attendance request not found.');
        }

        return $this->success($result, 'Request rejected.');
    }
}
