<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AdminService $adminService
    ) {}

    public function users(): JsonResponse
    {
        return $this->success($this->adminService->getUsers());
    }

    public function createUser(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|min:3|max:50',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'password' => 'required|string|min:8',
            'role' => 'required|in:hr_staff,accountant,system_admin,management',
            'department' => 'nullable|string|max:100',
        ]);

        $result = $this->adminService->createUser($request->all());

        return $this->created($result, 'User created successfully.');
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'role' => 'nullable|in:hr_staff,accountant,system_admin,management',
            'department' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $result = $this->adminService->updateUser($id, $request->all());

        if (!$result) {
            return $this->notFound('User not found.');
        }

        return $this->success($result, 'User updated successfully.');
    }

    public function resetPassword(int $id): JsonResponse
    {
        $result = $this->adminService->resetPassword($id);

        if (!$result) {
            return $this->notFound('User not found.');
        }

        return $this->success($result, 'Password reset successfully.');
    }

    public function roles(): JsonResponse
    {
        return $this->success($this->adminService->getRoles());
    }

    public function assignRoles(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'in:hr_staff,accountant,system_admin,management',
            'role' => 'nullable|in:hr_staff,accountant,system_admin,management',
        ]);

        $result = $this->adminService->assignRoles($id, $request->all());

        if (!$result) {
            return $this->notFound('User not found.');
        }

        return $this->success($result, 'Roles assigned successfully.');
    }
}
