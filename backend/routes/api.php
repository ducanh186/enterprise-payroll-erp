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
    Route::prefix('reference')->group(function () {
        Route::get('/shifts', [ReferenceController::class, 'shifts']);
        Route::get('/holidays', [ReferenceController::class, 'holidays']);
        Route::get('/contract-types', [ReferenceController::class, 'contractTypes']);
        Route::get('/payroll-types', [ReferenceController::class, 'payrollTypes']);
        Route::get('/payroll-parameters', [ReferenceController::class, 'payrollParameters']);
        Route::get('/late-early-rules', [ReferenceController::class, 'lateEarlyRules']);
        Route::get('/departments', [ReferenceController::class, 'departments']);
    });

    // -----------------------------------------------------------------------
    // Employee Module
    // -----------------------------------------------------------------------
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/{id}', [EmployeeController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/{id}/active-contract', [EmployeeController::class, 'activeContract'])->where('id', '[0-9]+');
        Route::get('/{id}/dependents', [EmployeeController::class, 'dependents'])->where('id', '[0-9]+');
    });

    // -----------------------------------------------------------------------
    // Contract Module
    // -----------------------------------------------------------------------
    Route::prefix('contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index']);
        Route::get('/{id}', [ContractController::class, 'show'])->where('id', '[0-9]+');
    });

    // -----------------------------------------------------------------------
    // Attendance Module (PRIORITY)
    // -----------------------------------------------------------------------
    Route::prefix('attendance')->group(function () {
        Route::get('/checkin-logs', [AttendanceController::class, 'checkinLogs']);
        Route::post('/checkin-logs/manual', [AttendanceController::class, 'manualCheckin']);
        Route::get('/daily', [AttendanceController::class, 'daily']);
        Route::get('/monthly-summary', [AttendanceController::class, 'monthlySummary']);
        Route::post('/recalculate', [AttendanceController::class, 'recalculate']);
        Route::get('/requests', [AttendanceController::class, 'requestsIndex']);
        Route::post('/requests', [AttendanceController::class, 'requestsStore']);
        Route::get('/requests/{id}', [AttendanceController::class, 'requestsShow'])->where('id', '[0-9]+');
        Route::post('/requests/{id}/approve', [AttendanceController::class, 'requestsApprove'])->where('id', '[0-9]+');
        Route::post('/requests/{id}/reject', [AttendanceController::class, 'requestsReject'])->where('id', '[0-9]+');
    });

    // -----------------------------------------------------------------------
    // Payroll Module (PRIORITY)
    // -----------------------------------------------------------------------
    Route::prefix('payroll')->group(function () {
        Route::get('/periods', [PayrollController::class, 'periods']);
        Route::post('/periods/open', [PayrollController::class, 'openPeriod']);
        Route::get('/runs/preview-parameters', [PayrollController::class, 'previewParameters']);
        Route::post('/runs/preview', [PayrollController::class, 'previewRun']);
        Route::get('/runs/{runId}', [PayrollController::class, 'showRun']);
        Route::post('/runs/{runId}/finalize', [PayrollController::class, 'finalizeRun']);
        Route::post('/runs/{runId}/lock', [PayrollController::class, 'lockRun']);
        Route::get('/payslips', [PayrollController::class, 'payslips']);
        Route::get('/payslips/{id}', [PayrollController::class, 'showPayslip'])->where('id', '[0-9]+');
        Route::get('/payslips/{id}/details', [PayrollController::class, 'payslipDetails'])->where('id', '[0-9]+');
        Route::post('/adjustments', [PayrollController::class, 'createAdjustment']);
        Route::put('/adjustments/{id}', [PayrollController::class, 'updateAdjustment'])->where('id', '[0-9]+');
        Route::delete('/adjustments/{id}', [PayrollController::class, 'deleteAdjustment'])->where('id', '[0-9]+');
    });

    // -----------------------------------------------------------------------
    // Reports Module
    // -----------------------------------------------------------------------
    Route::prefix('reports')->group(function () {
        Route::get('/templates', [ReportController::class, 'templates']);
        Route::post('/{code}/preview', [ReportController::class, 'preview']);
        Route::post('/{code}/export', [ReportController::class, 'export']);
    });

    // -----------------------------------------------------------------------
    // Admin Module (system_admin only)
    // -----------------------------------------------------------------------
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->where('id', '[0-9]+');
    Route::post('/users/{id}/reset-password', [AdminController::class, 'resetPassword'])->where('id', '[0-9]+');
    Route::get('/roles', [AdminController::class, 'roles']);
    Route::get('/permissions', [AdminController::class, 'permissions']);
    Route::get('/admin/permissions', [AdminController::class, 'permissions']);
    Route::post('/users/{id}/roles', [AdminController::class, 'assignRoles'])->where('id', '[0-9]+');
});
