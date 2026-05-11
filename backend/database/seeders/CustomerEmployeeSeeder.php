<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (!config('database.connections.customer_sqlsrv.database')) {
            return;
        }

        try {
            $customerEmployees = DB::connection('customer_sqlsrv')
                ->table('D20Employee')
                ->where('IsGroup', 0)
                ->where('IsActive', 1)
                ->get();
        } catch (\Throwable) {
            return;
        }

        foreach ($customerEmployees as $customerEmployee) {
            $employeeCode = Str::upper(trim((string) ($customerEmployee->Code ?? '')));
            if ($employeeCode === '') {
                continue;
            }

            $departmentId = $this->syncDepartment(trim((string) ($customerEmployee->DeptCode ?? '')));
            $userId = $this->syncUser($employeeCode, $customerEmployee);

            Employee::query()->updateOrCreate(
                ['employee_code' => $employeeCode],
                [
                    'user_id' => $userId,
                    'full_name' => trim((string) ($customerEmployee->FullName ?? $employeeCode)) ?: $employeeCode,
                    'dob' => $this->nullableDate($customerEmployee->BirthDate ?? null),
                    'gender' => $this->gender($customerEmployee->Gender ?? null),
                    'national_id' => $this->nullableString($customerEmployee->IdCardNo ?? null),
                    'tax_code' => $this->nullableString($customerEmployee->TaxRegNo ?? null),
                    'email' => $this->nullableString($customerEmployee->Email ?? null),
                    'phone' => $this->nullableString($customerEmployee->Mobile ?? null),
                    'bank_account_no' => $this->nullableString($customerEmployee->BankAccountNo ?? null),
                    'bank_name' => $this->nullableString($customerEmployee->BankName ?? null),
                    'department_id' => $departmentId,
                    'join_date' => $this->nullableDate($customerEmployee->FirstWorkingDate ?? null),
                    'employment_status' => 'active',
                ],
            );
        }
    }

    private function syncDepartment(string $deptCode): ?int
    {
        if ($deptCode === '') {
            return null;
        }

        $name = $deptCode;
        try {
            $department = DB::connection('customer_sqlsrv')
                ->table('D20Department')
                ->where('Code', $deptCode)
                ->first();
            $name = trim((string) ($department->Name ?? $deptCode)) ?: $deptCode;
        } catch (\Throwable) {
            // Keep the code as the department name when the customer lookup fails.
        }

        return (int) Department::query()->firstOrCreate(
            ['code' => $deptCode],
            ['name' => $name, 'status' => 'active'],
        )->id;
    }

    private function syncUser(string $employeeCode, object $customerEmployee): int
    {
        $slug = Str::lower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $employeeCode));
        $slug = trim($slug, '_') ?: Str::lower(Str::random(8));
        $username = 'fjm_' . substr($slug, 0, 40);

        return (int) User::query()->firstOrCreate(
            ['username' => $username],
            [
                'name' => trim((string) ($customerEmployee->FullName ?? $employeeCode)) ?: $employeeCode,
                'email' => $username . '@fujimart.local',
                'password' => Str::random(32),
                'phone' => $this->nullableString($customerEmployee->Mobile ?? null),
                'is_active' => false,
            ],
        )->id;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nullableDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function gender(mixed $value): ?string
    {
        return match ((int) $value) {
            1 => 'male',
            2 => 'female',
            default => null,
        };
    }
}
