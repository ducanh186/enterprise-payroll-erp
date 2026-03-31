<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Enterprise Payroll ERP
|--------------------------------------------------------------------------
|
| API routes for the payroll ERP. Login is public; the remaining
| domain endpoints are protected by Sanctum bearer tokens.
| Prefix: /api (applied automatically by Laravel)
|
*/

// -----------------------------------------------------------------------
// Auth Module (public)
// -----------------------------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // GET /api/me and /api/me/permissions
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/me/permissions', [AuthController::class, 'permissions']);

    // -----------------------------------------------------------------------
    // Reference / Master Data Module
    // -----------------------------------------------------------------------
    Route::prefix('reference')->middleware('permission:reference.view')->group(function () {
        Route::get('/shifts', [ReferenceController::class, 'shifts']);
        Route::get('/holidays', [ReferenceController::class, 'holidays']);
        Route::get('/contract-types', [ReferenceController::class, 'contractTypes']);
        Route::get('/payroll-types', [ReferenceController::class, 'payrollTypes']);
        Route::get('/payroll-parameters', [ReferenceController::class, 'payrollParameters']);
        Route::get('/late-early-rules', [ReferenceController::class, 'lateEarlyRules']);
        Route::get('/departments', [ReferenceController::class, 'departments']);
        Route::get('/salary-levels', [ReferenceController::class, 'salaryLevels']);
        Route::get('/allowances', [ReferenceController::class, 'allowances']);
    });

    // -----------------------------------------------------------------------
    // Employee Module
    // -----------------------------------------------------------------------
    Route::prefix('employees')->middleware('permission:employee.view')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/{id}', [EmployeeController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/{id}/active-contract', [EmployeeController::class, 'activeContract'])->where('id', '[0-9]+');
        Route::get('/{id}/dependents', [EmployeeController::class, 'dependents'])->where('id', '[0-9]+');
    });

    // -----------------------------------------------------------------------
    // Contract Module
    // -----------------------------------------------------------------------
    Route::prefix('contracts')->middleware('permission:contract.view')->group(function () {
        Route::get('/', [ContractController::class, 'index']);
        Route::get('/{id}', [ContractController::class, 'show'])->where('id', '[0-9]+');
    });

    // -----------------------------------------------------------------------
    // Attendance Module (PRIORITY)
    // -----------------------------------------------------------------------
    Route::prefix('attendance')->group(function () {
        Route::get('/checkin-logs', [AttendanceController::class, 'checkinLogs'])->middleware('permission:attendance.import_logs');
        Route::post('/checkin-logs/manual', [AttendanceController::class, 'manualCheckin'])->middleware('permission:attendance.manage_request');
        Route::get('/daily', [AttendanceController::class, 'daily'])->middleware('permission:attendance.view');
        Route::get('/monthly-summary', [AttendanceController::class, 'monthlySummary'])->middleware('permission:attendance.view');
        Route::post('/recalculate', [AttendanceController::class, 'recalculate'])->middleware('permission:attendance.calculate');
        Route::get('/requests', [AttendanceController::class, 'requestsIndex'])->middleware('permission:attendance.manage_request');
        Route::post('/requests', [AttendanceController::class, 'requestsStore'])->middleware('permission:attendance.manage_request');
        Route::get('/requests/{id}', [AttendanceController::class, 'requestsShow'])->where('id', '[0-9]+')->middleware('permission:attendance.manage_request');
        Route::post('/requests/{id}/approve', [AttendanceController::class, 'requestsApprove'])->where('id', '[0-9]+')->middleware('permission:attendance.confirm');
        Route::post('/requests/{id}/reject', [AttendanceController::class, 'requestsReject'])->where('id', '[0-9]+')->middleware('permission:attendance.confirm');
        Route::get('/shift-assignments', [AttendanceController::class, 'shiftAssignments'])->middleware('permission:attendance.manage_period');
    });

    // -----------------------------------------------------------------------
    // Payroll Module (PRIORITY)
    // -----------------------------------------------------------------------
    Route::prefix('payroll')->group(function () {
        Route::get('/periods', [PayrollController::class, 'periods'])->middleware('permission:payroll.view');
        Route::post('/periods/open', [PayrollController::class, 'openPeriod'])->middleware('permission:payroll.run');
        Route::get('/runs/preview-parameters', [PayrollController::class, 'previewParameters'])->middleware('permission:payroll.run');
        Route::post('/runs/preview', [PayrollController::class, 'previewRun'])->middleware('permission:payroll.run');
        Route::get('/runs/{runId}', [PayrollController::class, 'showRun'])->middleware('permission:payroll.view');
        Route::post('/runs/{runId}/finalize', [PayrollController::class, 'finalizeRun'])->middleware('permission:payroll.finalize');
        Route::post('/runs/{runId}/lock', [PayrollController::class, 'lockRun'])->middleware('permission:payroll.lock');
        Route::get('/payslips', [PayrollController::class, 'payslips'])->middleware('permission:payroll.view');
        Route::get('/payslips/{id}', [PayrollController::class, 'showPayslip'])->where('id', '[0-9]+')->middleware('permission:payroll.view');
        Route::get('/payslips/{id}/details', [PayrollController::class, 'payslipDetails'])->where('id', '[0-9]+')->middleware('permission:payroll.view');
        Route::post('/adjustments', [PayrollController::class, 'createAdjustment'])->middleware('permission:payroll.adjust');
        Route::put('/adjustments/{id}', [PayrollController::class, 'updateAdjustment'])->where('id', '[0-9]+')->middleware('permission:payroll.adjust');
        Route::delete('/adjustments/{id}', [PayrollController::class, 'deleteAdjustment'])->where('id', '[0-9]+')->middleware('permission:payroll.adjust');
        Route::get('/bonus-deductions', [PayrollController::class, 'bonusDeductions'])->middleware('permission:payroll.adjust');
    });

    // -----------------------------------------------------------------------
    // Reports Module
    // -----------------------------------------------------------------------
    Route::prefix('reports')->group(function () {
        Route::get('/templates', [ReportController::class, 'templates'])->middleware('permission:reports.view');
        Route::post('/{code}/preview', [ReportController::class, 'preview'])->middleware('permission:reports.view');
        Route::post('/{code}/export', [ReportController::class, 'export'])->middleware('permission:reports.export');
    });

    // -----------------------------------------------------------------------
    // Admin Module (system_admin only)
    // -----------------------------------------------------------------------
    Route::get('/users', [AdminController::class, 'users'])->middleware('permission:admin.users');
    Route::post('/users', [AdminController::class, 'createUser'])->middleware('permission:admin.users');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->where('id', '[0-9]+')->middleware('permission:admin.users');
    Route::post('/users/{id}/reset-password', [AdminController::class, 'resetPassword'])->where('id', '[0-9]+')->middleware('permission:admin.users');
    Route::get('/roles', [AdminController::class, 'roles'])->middleware('permission:admin.roles');
    Route::get('/permissions', [AdminController::class, 'permissions'])->middleware('permission:admin.roles');
    Route::get('/admin/permissions', [AdminController::class, 'permissions'])->middleware('permission:admin.roles');
    Route::post('/users/{id}/roles', [AdminController::class, 'assignRoles'])->where('id', '[0-9]+')->middleware('permission:admin.roles');
});
