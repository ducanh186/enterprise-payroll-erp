<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthService
{
    /**
     * Fallback mock users for when the DB is empty or unavailable.
     */
    private array $mockUsers = [
        [
            'id' => 1,
            'username' => 'admin01',
            'password' => 'password',
            'name' => 'Nguyen Van Admin',
            'email' => 'admin01@erp.vn',
            'role' => 'system_admin',
            'department_id' => 8,
            'department_name' => 'Phong Cong Nghe Thong Tin',
            'avatar' => null,
            'is_active' => true,
        ],
        [
            'id' => 2,
            'username' => 'hr01',
            'password' => 'password',
            'name' => 'Tran Thi HR',
            'email' => 'hr01@erp.vn',
            'role' => 'hr_staff',
            'department_id' => 2,
            'department_name' => 'Phong Nhan Su',
            'avatar' => null,
            'is_active' => true,
        ],
        [
            'id' => 4,
            'username' => 'payroll01',
            'password' => 'password',
            'name' => 'Pham Thi Payroll',
            'email' => 'payroll01@erp.vn',
            'role' => 'accountant',
            'department_id' => 3,
            'department_name' => 'Phong Ke Toan',
            'avatar' => null,
            'is_active' => true,
        ],
        [
            'id' => 5,
            'username' => 'manager01',
            'password' => 'password',
            'name' => 'Hoang Van Manager',
            'email' => 'manager01@erp.vn',
            'role' => 'management',
            'department_id' => 1,
            'department_name' => 'Ban Giam Doc',
            'avatar' => null,
            'is_active' => true,
        ],
    ];

    private const ROLE_PERMISSIONS = [
        'system_admin' => [
            'dashboard.view',
            'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
            'payroll.manage_param',
            'admin.users', 'admin.roles', 'admin.config', 'admin.audit', 'admin.backup',
        ],
        'hr_staff' => [
            'dashboard.view',
            'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
            'reference.view', 'reference.manage',
            'employee.view', 'employee.create', 'employee.update', 'employee.delete',
            'contract.view', 'contract.create', 'contract.update', 'contract.renew', 'contract.terminate',
            'attendance.view', 'attendance.manage_period', 'attendance.import_logs',
            'attendance.calculate', 'attendance.manage_request', 'attendance.confirm',
            'reports.view', 'reports.export',
        ],
        'accountant' => [
            'dashboard.view',
            'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
            'reference.view',
            'employee.view',
            'contract.view',
            'attendance.view',
            'payroll.view', 'payroll.adjust', 'payroll.run', 'payroll.finalize', 'payroll.lock',
            'reports.view', 'reports.export',
        ],
        'management' => [
            'dashboard.view',
            'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
            'reports.view', 'reports.export',
        ],
        'employee' => [
            'dashboard.view',
            'auth.login', 'auth.logout', 'auth.profile', 'auth.change_password',
            'self.attendance.view', 'self.request.manage', 'self.payslip.view',
        ],
    ];

    /**
     * Check whether the database is available and has users.
     */
    private function dbAvailable(): bool
    {
        try {
            return Schema::hasTable('users') && User::count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function login(string $username, string $password): ?array
    {
        // Try real DB auth first
        if ($this->dbAvailable()) {
            $user = User::where('username', $username)->first();

            if (!$user || !Hash::check($password, $user->password)) {
                return null;
            }

            if (!$user->is_active) {
                return null;
            }

            // Delete old tokens and create a new one
            $user->tokens()->delete();
            $token = $user->createToken('api-token')->plainTextToken;

            // Update last login timestamp
            $user->update(['last_login_at' => now()]);

            // Load roles and employee relation
            $user->load(['roles.permissions', 'employee.department']);

            $primaryRole = $user->roles->first()?->code ?? 'employee';
            $department = $user->employee?->department;

            return [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'user' => $this->formatUserPayload($user, $primaryRole, $department?->id ?? $user->employee?->department_id, $department?->name),
            ];
        }

        if (!$this->allowMockFallback()) {
            return null;
        }

        // Fallback to mock data
        foreach ($this->mockUsers as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                $userData = $user;
                unset($userData['password']);
                $userData['permissions'] = self::ROLE_PERMISSIONS[$userData['role']] ?? [];
                return [
                    'token' => 'mock-jwt-token-' . $user['id'] . '-' . bin2hex(random_bytes(16)),
                    'token_type' => 'Bearer',
                    'expires_in' => 86400,
                    'user' => $userData,
                ];
            }
        }
        return null;
    }

    public function logout(): bool
    {
        $user = Auth::user();
        if ($user) {
            /** @var User $user */
            $user->currentAccessToken()?->delete();
            return true;
        }
        return true;
    }

    public function me(): array
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user->load(['roles.permissions', 'employee.department']);

            $primaryRole = $user->roles->first()?->code ?? 'employee';
            $department = $user->employee?->department;

            return $this->formatUserPayload($user, $primaryRole, $department?->id ?? $user->employee?->department_id, $department?->name);
        }

        if (!$this->allowMockFallback()) {
            return [];
        }

        // Fallback to mock admin user
        $user = $this->mockUsers[0];
        unset($user['password']);
        $user['permissions'] = self::ROLE_PERMISSIONS[$user['role']] ?? [];

        return $user;
    }

    public function getPermissions(string $role = 'system_admin'): array
    {
        $user = Auth::user();

        if ($user instanceof User && $this->dbAvailable()) {
            $user->loadMissing('roles.permissions');
            $role = $user->roles->first()?->code ?? $role;

            return [
                'role' => $role,
                'role_label' => UserRole::tryFrom($role)?->label() ?? $role,
                'permissions' => $this->resolvePermissions($user, $role),
            ];
        }

        if (!$this->allowMockFallback()) {
            return [
                'role' => $role,
                'role_label' => UserRole::tryFrom($role)?->label() ?? $role,
                'permissions' => [],
            ];
        }

        return [
            'role' => $role,
            'role_label' => UserRole::tryFrom($role)?->label() ?? $role,
            'permissions' => $this->resolvePermissions($user instanceof User ? $user : null, $role),
        ];
    }

    private function allowMockFallback(): bool
    {
        return (bool) config('services.auth.allow_mock_fallback', false);
    }

    private function formatUserPayload(User $user, string $role, mixed $departmentId, ?string $departmentName): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'department_id' => $departmentId,
            'department_name' => $departmentName,
            'avatar' => null,
            'is_active' => $user->is_active,
            'permissions' => $this->resolvePermissions($user, $role),
        ];
    }

    private function resolvePermissions(?User $user, string $role): array
    {
        if ($user instanceof User && $this->dbAvailable()) {
            $user->loadMissing('roles.permissions');

            $permissions = $user->roles
                ->flatMap(fn ($assignedRole) => $assignedRole->permissions->pluck('code'))
                ->unique()
                ->values()
                ->toArray();

            if ($permissions !== []) {
                return $permissions;
            }
        }

        return self::ROLE_PERMISSIONS[$role] ?? [];
    }
}
