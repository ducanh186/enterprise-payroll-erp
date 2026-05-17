<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FujimartProcedureController extends Controller
{
    public function attendanceCalculate(Request $req): JsonResponse
    {
        $req->validate([
            'doc_date'      => 'required|date',
            'branch_code'   => 'nullable|string|max:16',
            'dept_code'     => 'nullable|string|max:16',
            'employee_code' => 'nullable|string|max:16',
        ]);

        return $this->execWithOutput(
            'dbo.usp_CreateAndCalculateAttendance',
            ['_DocDate1', '_BranchCode', '_DeptCode', '_EmployeeCode'],
            [
                $req->doc_date,
                $req->input('branch_code', ''),
                $req->input('dept_code', ''),
                $req->input('employee_code', ''),
            ],
        );
    }

    public function payrollCalculate(Request $req): JsonResponse
    {
        $req->validate([
            'doc_date'      => 'required|date',
            'branch_code'   => 'nullable|string|max:16',
            'dept_code'     => 'nullable|string|max:16',
            'employee_code' => 'nullable|string|max:16',
        ]);

        return $this->execWithOutput(
            'dbo.usp_CreateAndCalculatePayroll',
            ['_DocDate1', '_BranchCode', '_DeptCode', '_EmployeeCode'],
            [
                $req->doc_date,
                $req->input('branch_code', ''),
                $req->input('dept_code', ''),
                $req->input('employee_code', ''),
            ],
        );
    }

    public function attendanceReport(Request $req): JsonResponse
    {
        $req->validate([
            'doc_date1' => 'required|date',
            'doc_date2' => 'required|date|after_or_equal:doc_date1',
        ]);

        $rows = DB::select(
            'EXEC dbo.usp_AttendanceReport
                @_DocDate1=?, @_DocDate2=?,
                @_BranchCode=?, @_DeptCode=?, @_EmployeeCode=?',
            [
                $req->doc_date1,
                $req->doc_date2,
                $req->input('branch_code', ''),
                $req->input('dept_code', ''),
                $req->input('employee_code', ''),
            ],
        );

        return response()->json(['data' => $rows]);
    }

    public function payrollReport(Request $req): JsonResponse
    {
        $req->validate(['doc_date' => 'required|date']);

        $rows = DB::select(
            'EXEC dbo.usp_PayrollReport
                @_DocDate1=?, @_BranchCode=?, @_DeptCode=?, @_EmployeeCode=?',
            [
                $req->doc_date,
                $req->input('branch_code', ''),
                $req->input('dept_code', ''),
                $req->input('employee_code', ''),
            ],
        );

        return response()->json(['data' => $rows]);
    }

    public function payrollSlip(Request $req): JsonResponse
    {
        $req->validate([
            'doc_date'   => 'required|date',
            'send_email' => 'nullable|boolean',
        ]);

        $rows = DB::select(
            'EXEC dbo.usp_PayrollSlip
                @_DocDate1=?, @_EmployeeCode=?, @_DeptCode=?, @_BranchCode=?,
                @_SendEmail=?, @_MailProfile=?',
            [
                $req->doc_date,
                $req->input('employee_code', ''),
                $req->input('dept_code', ''),
                $req->input('branch_code', ''),
                $req->boolean('send_email') ? 1 : 0,
                $req->input('mail_profile', ''),
            ],
        );

        return response()->json(['data' => $rows]);
    }

    /**
     * Helper: execute a SP whose final parameter is an NVARCHAR(200) OUTPUT named @_result_msg.
     */
    private function execWithOutput(string $procedure, array $paramNames, array $paramValues): JsonResponse
    {
        $placeholders = implode(', ', array_map(
            fn ($n) => "@{$n}=?",
            $paramNames,
        ));

        $sql = "DECLARE @msg NVARCHAR(200);
                EXEC {$procedure} {$placeholders}, @_result_msg=@msg OUTPUT;
                SELECT @msg AS message;";

        $row = DB::selectOne($sql, $paramValues);

        return response()->json([
            'success' => true,
            'message' => $row->message ?? '',
        ]);
    }
}
