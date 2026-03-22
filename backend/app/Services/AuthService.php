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
            'username' => 'admin',
            'password' => 'password',
            'name' => 'Nguyen Van An',
            'email' => 'admin@payroll.vn',
            'role' => 'system_admin',
            'department_id' => 1,
            'department_name' => 'IT Department',
            'avatar' => null,
            'is_active' => true,
        ],
        [
            'id' => 2,
            'username' => 'hr_user',
            'password' => 'password',
            'name' => 'Tran Thi Bich',
            'email' => 'hr@payroll.vn',
            'role' => 'hr_staff',
            'department_id' => 2,
            'department_name' => 'Human Resources',
            'avatar' => null,
            'is_active' => true,
        ],
        [
            'id' => 3,
            'username' => 'accountant',
            'password' => 'password',
            'name' => 'Le Van Cuong',
            'email' => 'accountant@payroll.vn',
            'role' => 'accountant',
            'department_id' => 3,
            'department_name' => 'Finance & Accounting',
            'avatar' => null,
            'is_active' => true,
        ],
        [
            'id' => 4,
            'username' => 'manager',
            'password' => 'password',
            'name' => 'Pham Thi Dung',
            'email' => 'manager@payroll.vn',
            'role' => 'management',
            'department_id' => 4,
            'department_name' => 'Board of Directors',
            'avatar' => null,
            'is_active' => true,
        ],
    ];

    private const ROLE_PERMISSIONS = [
        'system_admin' => [
            'auth.*',
            'reference.read', 'reference.write',
            'employee.read', 'employee.write',
            'attendance.read', 'attendance.write',
            'payroll.read', 'payroll.write',
            'reports.read', 'reports.write',
            'admin.read', 'admin.write',
        ],
        'hr_staff' => [
            'auth.*',
            'reference.read',
            'employee.read', 'employee.write',
            'attendance.read', 'attendance.write',
            'payroll.read',
            'reports.read', 'reports.write',
        ],
        'accountant' => [
            'auth.*',
            'reference.read',
            'employee.read',
            'attendance.read',
            'payroll.read', 'payroll.write',
            'reports.read', 'reports.write',
        ],
        'management' => [
            'auth.*',
            'reference.read',
            'employee.read',
            'attendance.read',
            'payroll.read',
            'reports.read', 'reports.write',
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
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $primaryRole,
                    'department_id' => $department?->id ?? $user->employee?->department_id,
                    'department_name' => $department?->name,
                    'avatar' => null,
                    'is_active' => $user->is_active,
                ],
            ];
        }

        // Fallback to mock data
        foreach ($this->mockUsers as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                $userData = $user;
                unset($userData['password']);
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

            return [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $primaryRole,
                'department_id' => $department?->id ?? $user->employee?->department_id,
                'department_name' => $department?->name,
                'avatar' => null,
                'is_active' => $user->is_active,
            ];
        }

        // Fallback to mock admin user
        $user = $this->mockUsers[0];
        unset($user['password']);
        return $user;
    }

    public function getPermissions(string $role = 'system_admin'): array
    {
        $user = Auth::user();

        // If authenticated with DB, build permissions from roles
        if ($user instanceof User && $this->dbAvailable()) {
            $user->load('roles.permissions');

            $permissions = $user->roles
                ->flatMap(fn ($r) => $r->permissions->pluck('code'))
                ->unique()
                ->values()
                ->toArray();

            $primaryRole = $user->roles->first()?->code ?? $role;

            // If DB permissions are empty, fall back to static mapping
            if (empty($permissions)) {
                $permissions = self::ROLE_PERMISSIONS[$primaryRole] ?? [];
            }

            return [
                'role' => $primaryRole,
                'role_label' => UserRole::tryFrom($primaryRole)?->label() ?? $primaryRole,
                'permissions' => $permissions,
            ];
        }

        return [
            'role' => $role,
            'role_label' => UserRole::tryFrom($role)?->label() ?? $role,
            'permissions' => self::ROLE_PERMISSIONS[$role] ?? [],
        ];
    }
}
