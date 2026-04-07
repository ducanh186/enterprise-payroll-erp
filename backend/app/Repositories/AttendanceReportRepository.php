<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class AttendanceReportRepository
{
    /**
     * usp_Hrm_AttendanceCollection — Tổng hợp công
     */
    public function attendanceCollection(
        string $dateFrom,
        string $dateTo,
        string $employeeId = '',
        string $deptId = '',
        string $branchCode = 'A01',
        int $userId = 0
    ): array {
        return DB::select('EXEC dbo.usp_Hrm_AttendanceCollection ?, ?, ?, ?, ?, ?', [
            $dateFrom,
            $dateTo,
            $employeeId,
            $deptId,
            $branchCode,
            $userId,
        ]);
    }

    /**
     * usp_Hrm_AttendanceReport — Bảng chấm công
     */
    public function attendanceReport(
        string $dateFrom,
        string $dateTo,
        int $groupDeptLevel = 3,
        string $deptId = '',
        string $employeeId = '',
        int $showDataType = 0,
        int $symbolWorkday = 1,
        string $notInOutSymbol = '',
        string $notInSymbol = '',
        string $notOutSymbol = '',
        string $holiday1Symbol = '',
        string $holiday2Symbol = '',
        string $branchCode = '',
        int $userId = 0
    ): array {
        return DB::select(
            'EXEC dbo.usp_Hrm_AttendanceReport ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?',
            [
                $dateFrom,
                $dateTo,
                $groupDeptLevel,
                $deptId,
                $employeeId,
                $showDataType,
                $symbolWorkday,
                $notInOutSymbol,
                $notInSymbol,
                $notOutSymbol,
                $holiday1Symbol,
                $holiday2Symbol,
                $branchCode,
                $userId,
            ]
        );
    }

    /**
     * usp_Hrm_B30HrmAssignShift — Bảng phân ca hàng ngày
     */
    public function assignShiftReport(
        string $dateFrom,
        string $dateTo,
        ?string $employeeId = null,
        ?string $deptId = null,
        int $userId = 0,
        string $branchCode = 'A01'
    ): array {
        return DB::select('EXEC dbo.usp_Hrm_B30HrmAssignShift ?, ?, ?, ?, ?, ?', [
            $dateFrom,
            $dateTo,
            $employeeId,
            $deptId,
            $userId,
            $branchCode,
        ]);
    }

    /**
     * usp_Hrm_InOut_LaterEarly — Bảng tổng hợp đi trễ về sớm
     */
    public function lateEarlyReport(
        string $dateFrom,
        string $dateTo,
        ?string $employeeId = null,
        ?string $deptId = null,
        int $userId = 0,
        string $branchCode = 'A01'
    ): array {
        return DB::select('EXEC dbo.usp_Hrm_InOut_LaterEarly ?, ?, ?, ?, ?, ?', [
            $dateFrom,
            $dateTo,
            $employeeId,
            $deptId,
            $userId,
            $branchCode,
        ]);
    }
}
