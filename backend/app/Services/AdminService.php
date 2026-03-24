<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminService
{
    public function getUsers(): array
    {
        return User::query()
            ->with(['roles', 'employee.department'])
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => $this->formatUser($user))
            ->all();
    }

    public function createUser(array $data): array
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
        ]);

        $this->syncRolesByCodes($user, [$data['role']]);

        $user->load(['roles', 'employee.department']);

        return $this->formatUser($user, $data['department'] ?? null);
    }

    public function updateUser(int $id, array $data): ?array
    {
        $user = User::query()->with(['roles', 'employee.department'])->find($id);

        if (!$user) {
            return null;
        }

        $payload = [];

        foreach (['name', 'email', 'phone', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($payload !== []) {
            $user->fill($payload);
            $user->save();
        }

        if (!empty($data['role'])) {
            $this->syncRolesByCodes($user, [$data['role']]);
        }

        $user->load(['roles', 'employee.department']);

        return $this->formatUser($user, $data['department'] ?? null) + [
            'updated_at' => $this->dateTimeValue($user->updated_at),
        ];
    }

    public function resetPassword(int $id): ?array
    {
        $user = User::query()->find($id);

        if (!$user) {
            return null;
        }

        $temporaryPassword = Str::random(12) . '!';

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
        ])->save();

        return [
            'user_id' => $user->id,
            'username' => $user->username,
            'temporary_password' => $temporaryPassword,
            'must_change_password' => true,
            'message' => 'Password has been reset. Please provide the temporary password to the user securely.',
        ];
    }

    public function getRoles(): array
    {
        return Role::query()
            ->with(['permissions' => fn ($query) => $query->orderBy('module')->orderBy('code')])
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => $this->formatRole($role))
            ->all();
    }

    public function getPermissions(): array
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('code')
            ->get()
            ->map(fn (Permission $permission) => $this->formatPermission($permission))
            ->all();
    }

    public function assignRoles(int $userId, array $data): ?array
    {
        $user = User::query()->with(['roles', 'employee.department'])->find($userId);

        if (!$user) {
            return null;
        }

        $roleCodes = $this->normalizeRoleCodes($data);

        if ($roleCodes !== []) {
            $this->syncRolesByCodes($user, $roleCodes);
            $user->load('roles');
        }

        return [
            'user_id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'roles' => $user->roles->pluck('code')->values()->all(),
            'updated_at' => $this->dateTimeValue($user->updated_at),
        ];
    }

    protected function formatUser(User $user, ?string $departmentOverride = null): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $this->resolvePrimaryRole($user),
            'department' => data_get($user, 'employee.department.name') ?? $departmentOverride,
            'is_active' => (bool) $user->is_active,
            'last_login' => $this->dateTimeValue($user->last_login_at),
            'created_at' => $this->dateTimeValue($user->created_at),
        ];
    }

    protected function formatRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'code' => $role->code,
            'name' => $role->name,
            'value' => $role->code,
            'label' => $role->name,
            'permissions' => $role->permissions
                ->map(fn (Permission $permission) => $this->formatPermission($permission))
                ->values()
                ->all(),
        ];
    }

    protected function formatPermission(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'code' => $permission->code,
            'name' => $permission->name,
            'module' => $permission->module,
            'label' => $permission->name,
            'description' => $permission->name,
        ];
    }

    protected function resolvePrimaryRole(User $user): ?string
    {
        return $user->roles->first()?->code;
    }

    protected function syncRolesByCodes(User $user, array $roleCodes): void
    {
        $roleIds = Role::query()
            ->whereIn('code', array_values(array_unique(array_filter($roleCodes))))
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);
    }

    protected function normalizeRoleCodes(array $data): array
    {
        $roles = $data['roles'] ?? null;

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (!is_array($roles)) {
            $roles = [];
        }

        if ($roles === [] && !empty($data['role'])) {
            $roles = [$data['role']];
        }

        return array_values(array_unique(array_filter(array_map('strval', $roles))));
    }

    protected function dateTimeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->toISOString();
    }
}
