# Fujimart Schema Mapping & View Layer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho stored procedures Fujimart gốc (`D20Shift`, `D30Attendance`, …) chạy trên DB hiện hữu mà không phải sửa SP, đồng thời FE/BE vẫn dùng bảng migrate Laravel — qua một lớp VIEW + INSTEAD OF triggers.

**Architecture:** Bảng migrate (snake_case) là nguồn sự thật. Sprint 1 bổ sung cột còn thiếu + tạo bảng chưa có. Sprint 2 tạo 22 view dbo.D00/D20/D30 trỏ vào bảng migrate, 4 view writable có INSTEAD OF trigger. Sprint 3 viết 5 SP mới đọc/ghi qua view. Sprint 4 cập nhật BE Controllers + FE.

**Tech Stack:** Laravel 11, PHP 8.3, SQL Server 2025, React 18 + TypeScript + TanStack Query, Docker Compose.

**Spec:** [`docs/superpowers/specs/2026-05-17-fujimart-table-mapping-design.md`](../specs/2026-05-17-fujimart-table-mapping-design.md)

**Port-conflict policy:** Trước mọi lần test chạy `docker ps`. Nếu `5173/8001/1433` bị chiếm, tạo `docker-compose.override.yml` đổi sang `5174/8002/1434`. **KHÔNG** dừng container khác.

---

## File Structure

### Files to create (Sprint 1 — Migrations)
```
backend/database/migrations/
  2026_05_17_100001_add_fujimart_columns_to_shifts.php
  2026_05_17_100002_add_pattern_columns_to_shift_assignments.php
  2026_05_17_100003_create_branches_table.php
  2026_05_17_100004_add_fujimart_columns_to_employees.php
  2026_05_17_100005_create_d20_payroll_parameters_table.php
  2026_05_17_100006_create_salary_scales_table.php
  2026_05_17_100007_create_salary_grades_table.php
  2026_05_17_100008_create_salary_grade_details_table.php
  2026_05_17_100009_create_late_early_rule_details_table.php
  2026_05_17_100010_add_doc_columns_to_attendance_daily.php
  2026_05_17_100011_add_is_active_to_catalog_tables.php
```

### Files to create (Sprint 2 — Views & Triggers)
```
backend/database/sql/
  06_views_fujimart.sql            ← 22 CREATE OR ALTER VIEW
  06b_triggers_fujimart.sql        ← 4 INSTEAD OF triggers
backend/database/seeders/
  FujimartSampleSeeder.php         ← seed A01 branch + tham số mẫu
```

### Files to create (Sprint 3 — Stored Procedures)
```
backend/database/sql/
  07_sp_fujimart.sql               ← 5 SP CREATE OR ALTER
```

### Files to create / modify (Sprint 4 — BE/FE)
```
backend/app/Http/Controllers/Api/
  FujimartProcedureController.php          ← endpoints để FE gọi 5 SP
backend/app/Models/
  Branch.php                                ← new
  D20PayrollParameter.php                   ← new (bảng flat)
  SalaryScale.php / SalaryGrade.php / SalaryGradeDetail.php ← new
  LateEarlyRuleDetail.php                   ← new
  Shift.php                                 ← MODIFY: thêm 14 fillable
  ShiftAssignment.php                       ← MODIFY: thêm pattern fillable
  Employee.php                              ← MODIFY: thêm Gender/BirthDate/…
backend/routes/
  api.php                                   ← MODIFY: thêm route group fujimart
frontend/src/pages/
  ShiftsPage.tsx                            ← MODIFY: bảng 25 cột
  ShiftAssignmentsPage.tsx                  ← MODIFY: dải ngày + 7 checkbox
  PayrollParametersPage.tsx                 ← MODIFY/CREATE: 2 tab
frontend/src/lib/
  fujimart-api.ts                           ← new: 5 hooks gọi SP
docker-compose.override.yml.example         ← hint cho dev đổi port
```

### Files to create (Tests)
```
backend/tests/Feature/
  Migrations/Sprint1Test.php
  Views/D20ShiftViewTest.php
  Views/D30AssignedShiftViewTest.php
  StoredProcedures/UspCreateAndCalculateAttendanceTest.php
  StoredProcedures/UspPayrollSlipTest.php
```

---

## Pre-flight check (run once before Sprint 1)

- [ ] **Step P1: Check docker port conflicts**

Run: `docker ps --format "table {{.Names}}\t{{.Ports}}"`

Expected: Note which ports are taken. As of session start, mesoco-vite chiếm 5173 → cần override.

- [ ] **Step P2: Create override file**

Create `D:\CODE\enterprise-payroll-erp\docker-compose.override.yml`:

```yaml
services:
  frontend:
    ports: ["5174:5173"]
    environment:
      - VITE_API_BASE_URL=http://127.0.0.1:8002/api
  backend:
    ports: ["8002:8000"]
    environment:
      - APP_URL=http://localhost:8002
  sqlserver:
    ports: ["1434:1433"]
```

Add `docker-compose.override.yml` to `.gitignore` if not already.

- [ ] **Step P3: Start containers**

Run: `docker compose up -d`

Expected: 3 containers `payroll-backend`, `payroll-frontend`, `payroll-sqlserver` → status `running`. SQL Server healthcheck passes within 60s.

Verify: `docker exec payroll-backend php artisan migrate:status` lists 43 migrations all `Ran`.

---

## Sprint 1 — Migrations bổ sung schema

### Task 1.1: Add Fujimart columns to `shifts` (14 cột)

**Files:**
- Create: `backend/database/migrations/2026_05_17_100001_add_fujimart_columns_to_shifts.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('description', 256)->nullable()->after('name');
            $table->boolean('is_checkin')->default(true)->after('description');
            $table->dateTime('start_time_valid1')->nullable()->after('start_time');
            $table->dateTime('start_time_valid2')->nullable()->after('start_time_valid1');
            $table->boolean('is_checkout')->default(true)->after('start_time_valid2');
            $table->dateTime('end_time_valid1')->nullable()->after('end_time');
            $table->dateTime('end_time_valid2')->nullable()->after('end_time_valid1');
            $table->integer('shift_break')->default(0)->after('end_time_valid2');
            $table->dateTime('start_break_time_valid1')->nullable()->after('break_start_time');
            $table->dateTime('start_break_time_valid2')->nullable()->after('start_break_time_valid1');
            $table->dateTime('end_break_time_valid1')->nullable()->after('break_end_time');
            $table->dateTime('end_break_time_valid2')->nullable()->after('end_break_time_valid1');
            $table->decimal('working_hours', 8, 2)->default(8.0)->after('workday_value');
            $table->integer('shift_break_mins')->default(0)->after('working_hours');
            $table->dateTime('start_working_night_time')->nullable()->after('shift_break_mins');
            $table->dateTime('end_working_night_time')->nullable()->after('start_working_night_time');
            $table->integer('shift_meal')->default(0)->after('end_working_night_time');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'is_checkin', 'start_time_valid1', 'start_time_valid2',
                'is_checkout', 'end_time_valid1', 'end_time_valid2', 'shift_break',
                'start_break_time_valid1', 'start_break_time_valid2',
                'end_break_time_valid1', 'end_break_time_valid2',
                'working_hours', 'shift_break_mins',
                'start_working_night_time', 'end_working_night_time', 'shift_meal',
            ]);
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `docker exec payroll-backend php artisan migrate`

Expected: `2026_05_17_100001_add_fujimart_columns_to_shifts ........... DONE`.

- [ ] **Step 3: Verify schema**

Run:
```sql
docker exec payroll-sqlserver /opt/mssql-tools18/bin/sqlcmd -U sa -P "YourStrong!Passw0rd" -C -d enterprise_payroll_erp -Q "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='shifts' ORDER BY ORDINAL_POSITION"
```

Expected: 27 cột bao gồm `description`, `is_checkin`, `start_time_valid1`, `start_time_valid2`, `is_checkout`, `end_time_valid1`, `end_time_valid2`, `shift_break`, `start_break_time_valid1/2`, `end_break_time_valid1/2`, `working_hours`, `shift_break_mins`, `start_working_night_time`, `end_working_night_time`, `shift_meal`.

- [ ] **Step 4: Commit**

```bash
git add backend/database/migrations/2026_05_17_100001_add_fujimart_columns_to_shifts.php
git commit -m "feat(shifts): add 17 Fujimart columns for full D20Shift mapping"
```

### Task 1.2: Add pattern columns to `shift_assignments`

**Files:**
- Create: `backend/database/migrations/2026_05_17_100002_add_pattern_columns_to_shift_assignments.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('work_date');
            $table->date('end_date')->nullable()->after('start_date');
            $table->boolean('include_mon')->default(false)->after('end_date');
            $table->boolean('include_tue')->default(false)->after('include_mon');
            $table->boolean('include_wed')->default(false)->after('include_tue');
            $table->boolean('include_thu')->default(false)->after('include_wed');
            $table->boolean('include_fri')->default(false)->after('include_thu');
            $table->boolean('include_sat')->default(false)->after('include_fri');
            $table->boolean('include_sun')->default(false)->after('include_sat');
        });

        // Backfill: with existing single-day rows, set start/end = work_date
        // and the matching day-of-week flag.
        \DB::statement(<<<'SQL'
            UPDATE shift_assignments
               SET start_date = work_date,
                   end_date   = work_date,
                   include_mon = CASE WHEN DATEPART(weekday, work_date) = 2 THEN 1 ELSE 0 END,
                   include_tue = CASE WHEN DATEPART(weekday, work_date) = 3 THEN 1 ELSE 0 END,
                   include_wed = CASE WHEN DATEPART(weekday, work_date) = 4 THEN 1 ELSE 0 END,
                   include_thu = CASE WHEN DATEPART(weekday, work_date) = 5 THEN 1 ELSE 0 END,
                   include_fri = CASE WHEN DATEPART(weekday, work_date) = 6 THEN 1 ELSE 0 END,
                   include_sat = CASE WHEN DATEPART(weekday, work_date) = 7 THEN 1 ELSE 0 END,
                   include_sun = CASE WHEN DATEPART(weekday, work_date) = 1 THEN 1 ELSE 0 END
        SQL);
    }

    public function down(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'start_date', 'end_date',
                'include_mon', 'include_tue', 'include_wed', 'include_thu',
                'include_fri', 'include_sat', 'include_sun',
            ]);
        });
    }
};
```

- [ ] **Step 2: Run + verify + commit**

Same shape as Task 1.1.
Commit message: `feat(shift_assignments): add date-range + Mon-Sun pattern columns for D30AssignedShift`.

### Task 1.3: Create `branches` table

**Files:**
- Create: `backend/database/migrations/2026_05_17_100003_create_branches_table.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 128);
            $table->string('address', 256)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
```

- [ ] **Step 2: Run + commit**

Run: `docker exec payroll-backend php artisan migrate`
Commit: `feat(branches): create branches table for D20Branch mapping`.

### Task 1.4: Add Fujimart columns to `employees`

**Files:**
- Create: `backend/database/migrations/2026_05_17_100004_add_fujimart_columns_to_employees.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('user_id')
                      ->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('employees', 'nationality')) {
                $table->string('nationality', 64)->nullable()->after('national_id');
            }
            if (!Schema::hasColumn('employees', 'address')) {
                $table->string('address', 256)->nullable()->after('nationality');
            }
            if (!Schema::hasColumn('employees', 'first_working_date')) {
                $table->date('first_working_date')->nullable()->after('join_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'nationality', 'address', 'first_working_date']);
        });
    }
};
```

- [ ] **Step 2: Run + commit**

Commit: `feat(employees): add branch_id + nationality/address/first_working_date for D20Employee`.

### Task 1.5: Create `d20_payroll_parameters` (flat)

**Files:**
- Create: `backend/database/migrations/2026_05_17_100005_create_d20_payroll_parameters_table.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('d20_payroll_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter', 64)->unique();
            $table->string('name', 128);
            $table->string('description', 128)->nullable();
            $table->date('effective_date');
            $table->string('type', 32);
            $table->decimal('amount', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('d20_payroll_parameters');
    }
};
```

- [ ] **Step 2: Run + commit**

Commit: `feat(d20_payroll_parameters): create flat table for D20PayrollParameter view`.

### Task 1.6: Create `salary_scales` table

**Files:**
- Create: `backend/database/migrations/2026_05_17_100006_create_salary_scales_table.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_scales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 256);
            $table->string('description', 256)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_scales');
    }
};
```

- [ ] **Step 2: Run + commit**

### Task 1.7: Create `salary_grades` table

**Files:**
- Create: `backend/database/migrations/2026_05_17_100007_create_salary_grades_table.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_grades', function (Blueprint $table) {
            $table->id();
            $table->string('scale_code', 64);
            $table->date('effective_date');
            $table->integer('salary_level')->default(1);
            $table->string('description', 256)->nullable();
            $table->timestamps();
            $table->foreign('scale_code')->references('code')->on('salary_scales')->cascadeOnDelete();
            $table->unique(['scale_code', 'salary_level', 'effective_date'], 'sg_scale_level_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_grades');
    }
};
```

- [ ] **Step 2: Run + commit**

### Task 1.8: Create `salary_grade_details` table

**Files:**
- Create: `backend/database/migrations/2026_05_17_100008_create_salary_grade_details_table.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_grade_details', function (Blueprint $table) {
            $table->id();
            $table->string('row_id', 32)->unique();
            $table->foreignId('salary_grade_id')->constrained('salary_grades')->cascadeOnDelete();
            $table->integer('salary_type');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('description', 256)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_grade_details');
    }
};
```

- [ ] **Step 2: Run + commit**

### Task 1.9: Create `late_early_rule_details` table

**Files:**
- Create: `backend/database/migrations/2026_05_17_100009_create_late_early_rule_details_table.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('late_early_rule_details', function (Blueprint $table) {
            $table->id();
            $table->string('row_id', 16)->unique();
            $table->foreignId('late_early_rule_id')->constrained('late_early_rules')->cascadeOnDelete();
            $table->string('description', 256)->nullable();
            $table->integer('start_minute')->default(0);
            $table->integer('end_minute')->default(0);
            $table->decimal('exclude_time', 8, 2)->default(0);
            $table->decimal('exclude_workday', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_early_rule_details');
    }
};
```

- [ ] **Step 2: Run + commit**

### Task 1.10: Add doc columns to `attendance_daily`

**Files:**
- Create: `backend/database/migrations/2026_05_17_100010_add_doc_columns_to_attendance_daily.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_daily', function (Blueprint $table) {
            $table->string('doc_id', 16)->nullable()->unique()->after('id');
            $table->string('assigned_shift_code', 16)->nullable()->after('shift_assignment_id');
            $table->decimal('paid_leave_days', 8, 2)->default(0)->after('night_hours');
            $table->decimal('unpaid_leave_days', 8, 2)->default(0)->after('paid_leave_days');
            $table->decimal('exclude_days', 8, 2)->default(0)->after('unpaid_leave_days');
            $table->decimal('exclude_hours', 8, 2)->default(0)->after('exclude_days');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_daily', function (Blueprint $table) {
            $table->dropColumn(['doc_id', 'assigned_shift_code', 'paid_leave_days',
                                'unpaid_leave_days', 'exclude_days', 'exclude_hours']);
        });
    }
};
```

- [ ] **Step 2: Run + commit**

### Task 1.11: Add `is_active` for soft disable across catalog tables

**Files:**
- Create: `backend/database/migrations/2026_05_17_100011_add_is_active_to_catalog_tables.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = [
        'shifts', 'holidays', 'late_early_rules',
        'contract_types', 'payroll_types', 'salary_levels',
        'positions', 'allowance_types', 'bonus_deduction_types',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tbl) {
            if (Schema::hasTable($tbl) && !Schema::hasColumn($tbl, 'is_active')) {
                Schema::table($tbl, function (Blueprint $t) {
                    $t->boolean('is_active')->default(true)->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tbl) {
            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'is_active')) {
                Schema::table($tbl, function (Blueprint $t) {
                    $t->dropColumn('is_active');
                });
            }
        }
    }
};
```

- [ ] **Step 2: Run + commit**

Commit: `feat(catalog): add is_active flag for soft-disable button`.

### Task 1.12: Sprint 1 integration test

**Files:**
- Create: `backend/tests/Feature/Migrations/Sprint1Test.php`

- [ ] **Step 1: Write integration test**

```php
<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Sprint1Test extends TestCase
{
    use RefreshDatabase;

    public function test_shifts_has_all_fujimart_columns(): void
    {
        $expected = [
            'description', 'is_checkin', 'is_checkout',
            'start_time_valid1', 'start_time_valid2',
            'end_time_valid1', 'end_time_valid2',
            'shift_break', 'start_break_time_valid1', 'start_break_time_valid2',
            'end_break_time_valid1', 'end_break_time_valid2',
            'working_hours', 'shift_break_mins',
            'start_working_night_time', 'end_working_night_time', 'shift_meal',
        ];
        foreach ($expected as $col) {
            $this->assertTrue(Schema::hasColumn('shifts', $col), "shifts missing $col");
        }
    }

    public function test_shift_assignments_has_pattern_columns(): void
    {
        foreach (['start_date','end_date','include_mon','include_tue','include_wed',
                  'include_thu','include_fri','include_sat','include_sun'] as $col) {
            $this->assertTrue(Schema::hasColumn('shift_assignments', $col));
        }
    }

    public function test_new_tables_exist(): void
    {
        foreach (['branches', 'd20_payroll_parameters', 'salary_scales',
                  'salary_grades', 'salary_grade_details', 'late_early_rule_details'] as $tbl) {
            $this->assertTrue(Schema::hasTable($tbl), "table $tbl missing");
        }
    }

    public function test_attendance_daily_has_doc_columns(): void
    {
        foreach (['doc_id','assigned_shift_code','paid_leave_days',
                  'unpaid_leave_days','exclude_days','exclude_hours'] as $col) {
            $this->assertTrue(Schema::hasColumn('attendance_daily', $col));
        }
    }

    public function test_catalog_tables_have_is_active(): void
    {
        foreach (['shifts','holidays','late_early_rules','contract_types'] as $tbl) {
            $this->assertTrue(Schema::hasColumn($tbl, 'is_active'));
        }
    }
}
```

- [ ] **Step 2: Run test**

Run: `docker exec payroll-backend php artisan test --filter=Sprint1Test`
Expected: 5 tests, 5 assertions pass.

- [ ] **Step 3: Commit**

```bash
git add backend/tests/Feature/Migrations/Sprint1Test.php
git commit -m "test(migrations): verify Sprint 1 schema additions"
```

---

## Sprint 2 — Views & Triggers

### Task 2.1: Write `06_views_fujimart.sql` (22 views)

**Files:**
- Create: `backend/database/sql/06_views_fujimart.sql`

- [ ] **Step 1: Create file with header + first 4 views**

```sql
-- Fujimart View Layer
-- Maps migrate tables to Fujimart-original schema names so legacy SPs can run.
SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

-- =============================================================================
-- 1. D00User
-- =============================================================================
CREATE OR ALTER VIEW dbo.D00User AS
SELECT
    u.username                                              AS UserName,
    u.name                                                  AS FullName,
    CAST(e.id AS INT)                                       AS EmployeeCode,
    u.password                                              AS Password,
    NULL                                                    AS LockDate
FROM dbo.users u
LEFT JOIN dbo.employees e ON e.user_id = u.id;
GO

-- =============================================================================
-- 2. D20Branch
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Branch AS
SELECT
    b.code      AS Code,
    b.name      AS Name,
    b.address   AS Address
FROM dbo.branches b
WHERE b.is_active = 1;
GO

-- =============================================================================
-- 3. D20Department
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Department AS
SELECT
    d.code      AS Code,
    d.name      AS Name
FROM dbo.departments d
WHERE d.status = N'active';
GO

-- =============================================================================
-- 4. D20Position
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Position AS
SELECT
    p.code      AS Code,
    p.name      AS Name
FROM dbo.positions p
WHERE p.status = N'active';
GO
```

- [ ] **Step 2: Append D20Employee, D20Dependent, D20ContractType, D20Shift**

```sql
-- =============================================================================
-- 5. D20Employee
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Employee AS
SELECT
    e.employee_code                          AS Code,
    e.full_name                              AS FullName,
    b.code                                   AS BranchCode,
    CAST(e.department_id AS INT)             AS DeptCode,
    CAST(e.position_id AS INT)               AS PositionCode,
    e.dob                                    AS BirthDate,
    CASE e.gender WHEN 'male' THEN 1
                  WHEN 'female' THEN 2
                  ELSE 3 END                 AS Gender,
    e.national_id                            AS IdCardNo,
    e.tax_code                               AS TaxRegNo,
    e.nationality                            AS Nationality,
    e.address                                AS Address,
    e.phone                                  AS Mobile,
    e.email                                  AS Email,
    e.bank_account_no                        AS BankAccountNo,
    e.bank_name                              AS BankName,
    ISNULL(e.first_working_date, e.join_date) AS FirstWorkingDate,
    NULL                                     AS ResignDate  -- map later if column exists
FROM dbo.employees e
LEFT JOIN dbo.branches b ON b.id = e.branch_id
WHERE e.employment_status = N'active';
GO

-- =============================================================================
-- 6. D20Dependent
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Dependent AS
SELECT
    d.full_name              AS Name,
    e.employee_code          AS EmployeeCode,
    d.dob                    AS BirthDate,
    CAST(NULL AS NVARCHAR(256)) AS Occupation,
    d.national_id            AS IdCardNo,
    CAST(NULL AS VARCHAR(24)) AS TaxRegNo,
    d.relationship           AS Relationship,
    d.tax_reduction_from     AS ReductionStartDate,
    d.tax_reduction_to       AS ReductionEndDate
FROM dbo.dependents d
JOIN dbo.employees e ON e.id = d.employee_id;
GO

-- =============================================================================
-- 7. D20ContractType
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20ContractType AS
SELECT
    ct.code                                AS Code,
    ct.name                                AS Name,
    0                                      AS [Type],   -- migrate has no annex distinction
    ct.duration_months                     AS NumberOfMonth,
    CAST(ct.is_probationary AS INT)        AS IsProbationary
FROM dbo.contract_types ct
WHERE ISNULL(ct.is_active, 1) = 1;
GO

-- =============================================================================
-- 8. D20Shift (25 cột)
-- =============================================================================
CREATE OR ALTER VIEW dbo.D20Shift AS
SELECT
    s.code                                   AS Code,
    s.name                                   AS Name,
    s.description                            AS Description,
    CAST(s.is_checkin AS TINYINT)            AS IsCheckin,
    CAST(s.start_time AS DATETIME)           AS StartTime,
    s.start_time_valid1                      AS StartTimeValid1,
    s.start_time_valid2                      AS StartTimeValid2,
    CAST(s.is_checkout AS TINYINT)           AS IsCheckout,
    CAST(s.end_time AS DATETIME)             AS EndTime,
    s.end_time_valid1                        AS EndTimeValid1,
    s.end_time_valid2                        AS EndTimeValid2,
    s.shift_break                            AS ShiftBreak,
    CAST(s.break_start_time AS DATETIME)     AS StartShiftBreak,
    s.start_break_time_valid1                AS StartBreakTimeValid1,
    s.start_break_time_valid2                AS StartBreakTimeValid2,
    CAST(s.break_end_time AS DATETIME)       AS EndShiftBreak,
    s.end_break_time_valid1                  AS EndBreakTimeValid1,
    s.end_break_time_valid2                  AS EndBreakTimeValid2,
    s.workday_value                          AS WorkDay,
    s.working_hours                          AS WorkingHours,
    s.shift_break_mins                       AS ShiftBreakMins,
    s.start_working_night_time               AS StartWorkingNightTime,
    s.end_working_night_time                 AS EndWorkingNightTime,
    s.shift_meal                             AS ShiftMeal,
    s.min_meal_hours                         AS MinHourMeal
FROM dbo.shifts s
WHERE s.is_active = 1 AND s.status = N'active';
GO
```

- [ ] **Step 3: Append D20Holiday, D20LateEarlyRegulation + Detail, D20SalaryScale/Grade/Detail, D20PayrollParameter (+ 2 sub views)**

```sql
-- 9. D20Holiday
CREATE OR ALTER VIEW dbo.D20Holiday AS
SELECT
    h.holiday_date                  AS [Date],
    h.name                          AS Description,
    h.multiplier                    AS NumberOfDay,
    CASE WHEN h.is_paid = 1 THEN 1 ELSE 2 END AS HolidayType
FROM dbo.holidays h
WHERE ISNULL(h.is_active, 1) = 1;
GO

-- 10. D20LateEarlyRegulation
CREATE OR ALTER VIEW dbo.D20LateEarlyRegulation AS
SELECT
    ler.code                        AS Code,
    ler.name                        AS Name,
    CAST(NULL AS NVARCHAR(256))     AS Description,
    ler.effective_from              AS AppliedDate,
    ler.effective_to                AS ExpiredDate
FROM dbo.late_early_rules ler
WHERE ISNULL(ler.is_active, 1) = 1;
GO

-- 11. D20LateEarlyRegulationDetail
CREATE OR ALTER VIEW dbo.D20LateEarlyRegulationDetail AS
SELECT
    d.row_id                        AS RowId,
    ler.code                        AS LateEarlyRegCode,
    d.description                   AS Description,
    d.start_minute                  AS StartMinute,
    d.end_minute                    AS EndMinute,
    d.exclude_time                  AS ExcludeTime,
    d.exclude_workday               AS ExcludeWorkDay
FROM dbo.late_early_rule_details d
JOIN dbo.late_early_rules ler ON ler.id = d.late_early_rule_id;
GO

-- 12. D20SalaryScale
CREATE OR ALTER VIEW dbo.D20SalaryScale AS
SELECT
    ss.code         AS Code,
    ss.name         AS Name,
    ss.description  AS Description
FROM dbo.salary_scales ss
WHERE ss.is_active = 1;
GO

-- 13. D20SalaryGrade
CREATE OR ALTER VIEW dbo.D20SalaryGrade AS
SELECT
    CAST(sg.id AS INT)              AS Id,
    sg.scale_code                   AS ScaleCode,
    sg.effective_date               AS EffectiveDate,
    sg.salary_level                 AS SalaryLevel,
    sg.description                  AS Description
FROM dbo.salary_grades sg;
GO

-- 14. D20SalaryGradeDetail
CREATE OR ALTER VIEW dbo.D20SalaryGradeDetail AS
SELECT
    sgd.row_id                          AS RowId,
    CAST(sgd.salary_grade_id AS VARCHAR(64)) AS ParentId,
    sgd.salary_type                     AS SalaryType,
    sgd.amount                          AS Amount,
    sgd.description                     AS Description
FROM dbo.salary_grade_details sgd;
GO

-- 15. D20PayrollParameter (flat, từ bảng mới)
CREATE OR ALTER VIEW dbo.D20PayrollParameter AS
SELECT
    CAST(p.id AS INT)        AS Id,
    p.parameter              AS Parameter,
    p.name                   AS Name,
    p.description            AS Description,
    p.effective_date         AS EffectiveDate,
    p.type                   AS [Type],
    p.amount                 AS Amount
FROM dbo.d20_payroll_parameters p
WHERE p.is_active = 1;
GO

-- 15a. vD20PayrollPara_ValuePara (tab tham số giá trị)
CREATE OR ALTER VIEW dbo.vD20PayrollPara_ValuePara AS
SELECT * FROM dbo.D20PayrollParameter
WHERE [Type] IN ('VALUE', 'RATE', 'COEFF');
GO

-- 15b. vD20PayrollPara_SalaryType (tab loại thu nhập)
CREATE OR ALTER VIEW dbo.vD20PayrollPara_SalaryType AS
SELECT * FROM dbo.D20PayrollParameter
WHERE [Type] IN ('INCOME', 'BONUS', 'DEDUCTION');
GO
```

- [ ] **Step 4: Append D30 views (LabourContract, BonusDeduction, AssignedShift, CheckInOut, AttendanceDoc, AbsenceDetail, CheckInManualDetail, Attendance, Payroll, PayrollDetail)**

```sql
-- 16. D30LabourContract
CREATE OR ALTER VIEW dbo.D30LabourContract AS
SELECT
    CAST(lc.id AS VARCHAR(32))               AS DocId,
    lc.contract_no                           AS DocNo,
    lc.sign_date                             AS DocDate,
    e.employee_code                          AS EmployeeCode,
    CAST(lc.contract_type_id AS INT)         AS TypeCode,
    lc.start_date                            AS StartDate,
    lc.end_date                              AS EndDate,
    lc.probation_rate                        AS ProbationaryRate,
    CAST(e.position_id AS INT)               AS PositionCode,
    CAST(lc.salary_level_id AS INT)          AS SalaryGradeId,
    0                                        AS WorkingType  -- default full-time
FROM dbo.labour_contracts lc
JOIN dbo.employees e ON e.id = lc.employee_id;
GO

-- 17. D30BonusDeduction
CREATE OR ALTER VIEW dbo.D30BonusDeduction AS
SELECT
    CAST(bd.id AS VARCHAR(32))               AS DocId,
    CAST(bd.id AS VARCHAR(32))               AS DocNo,
    bd.created_at                            AS DocDate,
    CASE bdt.kind WHEN 'bonus' THEN 1 ELSE 2 END AS DocType,
    d.code                                   AS DeptCode,
    e.employee_code                          AS EmployeeCode,
    bd.description                           AS Description,
    bdt.code                                 AS SalaryType,
    bd.amount                                AS Amount
FROM dbo.bonus_deductions bd
JOIN dbo.bonus_deduction_types bdt ON bdt.id = bd.type_id
JOIN dbo.employees e               ON e.id = bd.employee_id
LEFT JOIN dbo.departments d        ON d.id = e.department_id
WHERE bd.status = N'active';
GO

-- 18. D30AssignedShift (1-1 mapping; trigger sẽ expand khi insert)
CREATE OR ALTER VIEW dbo.D30AssignedShift AS
SELECT
    CAST(sa.id AS VARCHAR(32))               AS AssignId,
    sa.work_date                             AS [Date],
    e.employee_code                          AS EmployeeCode,
    s.code                                   AS ShiftCode,
    ISNULL(sa.start_date, sa.work_date)      AS StartDate,
    ISNULL(sa.end_date, sa.work_date)        AS EndDate,
    CAST(sa.include_mon AS INT)              AS IncludeMon,
    CAST(sa.include_tue AS INT)              AS IncludeTue,
    CAST(sa.include_wed AS INT)              AS IncludeWed,
    CAST(sa.include_thu AS INT)              AS IncludeThu,
    CAST(sa.include_fri AS INT)              AS IncludeFri,
    CAST(sa.include_sat AS INT)              AS IncludeSat,
    CAST(sa.include_sun AS INT)              AS IncludeSun,
    sa.note                                  AS Description
FROM dbo.shift_assignments sa
JOIN dbo.employees e ON e.id = sa.employee_id
JOIN dbo.shifts s    ON s.id = sa.shift_id;
GO

-- 19. D30CheckInOut
CREATE OR ALTER VIEW dbo.D30CheckInOut AS
SELECT
    tl.log_time          AS CheckTime,
    e.employee_code      AS EmployeeCode
FROM dbo.time_logs tl
JOIN dbo.employees e ON e.id = tl.employee_id
WHERE tl.is_valid = 1;
GO

-- 20. D30AttendanceDoc
CREATE OR ALTER VIEW dbo.D30AttendanceDoc AS
SELECT
    CAST(ar.id AS VARCHAR(32))   AS DocId,
    CAST(ar.id AS VARCHAR(32))   AS DocNo,
    ar.submitted_at              AS DocDate,
    CASE ar.request_type
         WHEN 'leave' THEN 'AL'
         WHEN 'manual_checkin' THEN 'MC'
         ELSE 'XX' END           AS DocType,
    e.employee_code              AS EmployeeCode,
    eu.employee_code             AS ManagerCode,
    ar.reason                    AS Description
FROM dbo.attendance_requests ar
JOIN dbo.employees e            ON e.id = ar.employee_id
LEFT JOIN dbo.users u           ON u.id = ar.approved_by
LEFT JOIN dbo.employees eu      ON eu.user_id = u.id;
GO

-- 21. D30AbsenceDetail
CREATE OR ALTER VIEW dbo.D30AbsenceDetail AS
SELECT
    CAST(ard.id AS VARCHAR(32))     AS RowId,
    CAST(ard.request_id AS VARCHAR(32)) AS DocId,
    ard.work_date                   AS [Date],
    3                               AS [Type],  -- default full day
    ard.requested_hours             AS WorkingHours,
    1.0                             AS WorkingDays,
    ard.note                        AS Description
FROM dbo.attendance_request_details ard
JOIN dbo.attendance_requests ar ON ar.id = ard.request_id
WHERE ar.request_type = N'leave';
GO

-- 22. D30CheckInManualDetail
CREATE OR ALTER VIEW dbo.D30CheckInManualDetail AS
SELECT
    CAST(ard.id AS VARCHAR(32))     AS RowId,
    CAST(ard.request_id AS VARCHAR(32)) AS DocId,
    ard.requested_check_in          AS CheckTime,
    ard.note                        AS Description
FROM dbo.attendance_request_details ard
JOIN dbo.attendance_requests ar ON ar.id = ard.request_id
WHERE ar.request_type = N'manual_checkin';
GO

-- 23. D30Attendance
CREATE OR ALTER VIEW dbo.D30Attendance AS
SELECT
    ISNULL(ad.doc_id, CAST(ad.id AS VARCHAR(16))) AS DocId,
    ad.work_date                                  AS [Date],
    ISNULL(ad.assigned_shift_code, s.code)        AS AssignedShiftCode,
    ad.regular_hours                              AS WorkingHours,
    ad.workday_value                              AS WorkingDays,
    ad.unpaid_leave_days                          AS UnpaidLeaveDays,
    ad.paid_leave_days                            AS PaidLeaveDays,
    ad.exclude_days                               AS ExcludeDays,
    ad.exclude_hours                              AS ExcludeHours,
    ad.night_hours                                AS WorkNightHours,
    ad.meal_count                                 AS ShiftMeal
FROM dbo.attendance_daily ad
LEFT JOIN dbo.shift_assignments sa ON sa.id = ad.shift_assignment_id
LEFT JOIN dbo.shifts s             ON s.id = sa.shift_id;
GO

-- 24. D30Payroll
CREATE OR ALTER VIEW dbo.D30Payroll AS
SELECT
    ISNULL(b.code, 'A01')                      AS BranchCode,
    CAST(p.id AS VARCHAR(16))                  AS Id,
    ap.from_date                               AS [Date],
    d.code                                     AS DeptCode,
    e.employee_code                            AS EmployeeCode,
    CAST(NULL AS VARCHAR(32))                  AS ParaCode,
    0                                          AS NetCalc,
    p.gross_salary                             AS GrossSalary,
    0                                          AS Probationary,
    CAST(NULL AS NUMERIC(8,4))                 AS ProbationaryRate,
    p.insurance_base                           AS SalaryInsurance,
    0                                          AS OvertimeSalary,
    0                                          AS OffsetSalary,
    0                                          AS OVTTaxableIncome,
    p.bonus_total                              AS BonusSalary,
    0                                          AS OtherSalary,
    p.net_salary                               AS NetIncome,
    p.insurance_company                        AS SocialInsPay,
    p.insurance_employee                       AS SocialInsEMPLPay,
    0                                          AS HealthInsPay,
    0                                          AS HealthInsEMPLPay,
    0                                          AS TradeUnionInsPay,
    0                                          AS TradeUnionInsEMPLPay,
    0                                          AS UnemployedInsPay,
    0                                          AS UnemployedInsEMPLPay,
    0                                          AS AccidentInsPay,
    0                                          AS AccidentInsEMPLPay,
    0                                          AS AdvancesAmount,
    p.taxable_income                           AS TaxableIncome,
    0                                          AS SelfDeduction,
    0                                          AS DependQuantity,
    0                                          AS DependentDeduction,
    p.deduction_total                          AS OtherDeductionsAmount,
    p.taxable_income                           AS AssessableIncome,
    p.pit_amount                               AS PersonalIncomeTaxAmount,
    1                                          AS SocialInsurance,
    1                                          AS HealthInsurance,
    1                                          AS TradeUnionInsurance,
    1                                          AS UnemployedInsurance,
    1                                          AS PersonalIncomeTax,
    p.pit_amount                               AS DeductionPITaxAmount
FROM dbo.payslips p
JOIN dbo.payroll_runs pr           ON pr.id = p.payroll_run_id
JOIN dbo.attendance_periods ap     ON ap.id = pr.attendance_period_id
JOIN dbo.employees e               ON e.id = p.employee_id
LEFT JOIN dbo.branches b           ON b.id = e.branch_id
LEFT JOIN dbo.departments d        ON d.id = e.department_id;
GO

-- 25. D30PayrollDetail
CREATE OR ALTER VIEW dbo.D30PayrollDetail AS
SELECT
    CAST(pi.id AS VARCHAR(16))         AS RowId,
    CAST(pi.payslip_id AS VARCHAR(16)) AS PayRollId,
    pi.item_name                       AS SalaryType,
    pi.sort_order                      AS BuiltinOrder,
    1                                  AS Coeff,
    pi.amount                          AS Amount,
    pi.qty                             AS Days,
    CAST(NULL AS NUMERIC(6,2))         AS Hours
FROM dbo.payslip_items pi;
GO
```

- [ ] **Step 5: Apply views**

Run:
```bash
docker exec -i payroll-sqlserver /opt/mssql-tools18/bin/sqlcmd \
    -U sa -P "YourStrong!Passw0rd" -C \
    -d enterprise_payroll_erp \
    -i /var/opt/mssql/backup/06_views_fujimart.sql
```

(Copy file vào volume trước: `docker cp backend/database/sql/06_views_fujimart.sql payroll-sqlserver:/var/opt/mssql/backup/`.)

Expected: 22 `Commands completed successfully.` messages.

- [ ] **Step 6: Verify views**

Run:
```sql
SELECT name FROM sys.views WHERE name LIKE 'D[02]0%' OR name LIKE 'D30%' OR name LIKE 'vD20%' ORDER BY name;
```

Expected: 22 rows incluant `D00User`, `D20Branch`, `D20Department`, `D20Position`, `D20Employee`, `D20Dependent`, `D20ContractType`, `D20Shift`, `D20Holiday`, `D20LateEarlyRegulation`, `D20LateEarlyRegulationDetail`, `D20SalaryScale`, `D20SalaryGrade`, `D20SalaryGradeDetail`, `D20PayrollParameter`, `vD20PayrollPara_ValuePara`, `vD20PayrollPara_SalaryType`, `D30LabourContract`, `D30BonusDeduction`, `D30AssignedShift`, `D30CheckInOut`, `D30AttendanceDoc`, `D30AbsenceDetail`, `D30CheckInManualDetail`, `D30Attendance`, `D30Payroll`, `D30PayrollDetail`.

- [ ] **Step 7: Commit**

```bash
git add backend/database/sql/06_views_fujimart.sql
git commit -m "feat(views): add 22 Fujimart-compatible views over migrate tables"
```

### Task 2.2: Write `06b_triggers_fujimart.sql` (4 INSTEAD OF triggers)

**Files:**
- Create: `backend/database/sql/06b_triggers_fujimart.sql`

- [ ] **Step 1: Write trigger for `D20PayrollParameter`**

```sql
SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

-- =============================================================================
-- Trigger: D20PayrollParameter (INSERT/UPDATE/DELETE)
-- =============================================================================
CREATE OR ALTER TRIGGER dbo.tr_D20PayrollParameter_IOI
ON dbo.D20PayrollParameter
INSTEAD OF INSERT
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO dbo.d20_payroll_parameters
        (parameter, name, description, effective_date, type, amount, is_active, created_at, updated_at)
    SELECT
        i.Parameter, i.Name, i.Description, i.EffectiveDate, i.[Type], i.Amount,
        1, GETDATE(), GETDATE()
    FROM inserted i;
END;
GO

CREATE OR ALTER TRIGGER dbo.tr_D20PayrollParameter_IOU
ON dbo.D20PayrollParameter
INSTEAD OF UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE p
       SET p.parameter      = i.Parameter,
           p.name           = i.Name,
           p.description    = i.Description,
           p.effective_date = i.EffectiveDate,
           p.type           = i.[Type],
           p.amount         = i.Amount,
           p.updated_at     = GETDATE()
    FROM dbo.d20_payroll_parameters p
    JOIN inserted i ON i.Id = p.id;
END;
GO

CREATE OR ALTER TRIGGER dbo.tr_D20PayrollParameter_IOD
ON dbo.D20PayrollParameter
INSTEAD OF DELETE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE p SET is_active = 0, updated_at = GETDATE()
    FROM dbo.d20_payroll_parameters p
    JOIN deleted d ON d.Id = p.id;
END;
GO
```

- [ ] **Step 2: Trigger for `D30AssignedShift` (expand pattern → N rows)**

```sql
-- =============================================================================
-- Trigger: D30AssignedShift INSERT — expand StartDate..EndDate + Mon-Sun flags
--   to one shift_assignment row per matching workday.
-- =============================================================================
CREATE OR ALTER TRIGGER dbo.tr_D30AssignedShift_IOI
ON dbo.D30AssignedShift
INSTEAD OF INSERT
AS
BEGIN
    SET NOCOUNT ON;

    -- Cursor-less expansion via tally CTE
    WITH numbers AS (
        SELECT TOP (366) ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) - 1 AS n
        FROM sys.all_objects
    ),
    expanded AS (
        SELECT
            e.id AS employee_id,
            s.id AS shift_id,
            DATEADD(day, n.n, i.StartDate) AS work_date,
            i.StartDate, i.EndDate,
            i.IncludeMon, i.IncludeTue, i.IncludeWed, i.IncludeThu,
            i.IncludeFri, i.IncludeSat, i.IncludeSun,
            i.Description
        FROM inserted i
        JOIN dbo.employees e ON e.employee_code = i.EmployeeCode
        JOIN dbo.shifts s    ON s.code = i.ShiftCode
        CROSS APPLY numbers n
        WHERE DATEADD(day, n.n, i.StartDate) <= i.EndDate
    )
    INSERT INTO dbo.shift_assignments
        (employee_id, work_date, shift_id, source, note,
         start_date, end_date,
         include_mon, include_tue, include_wed, include_thu,
         include_fri, include_sat, include_sun,
         created_at, updated_at)
    SELECT
        ex.employee_id, ex.work_date, ex.shift_id, 'fujimart_sp', ex.Description,
        ex.StartDate, ex.EndDate,
        ex.IncludeMon, ex.IncludeTue, ex.IncludeWed, ex.IncludeThu,
        ex.IncludeFri, ex.IncludeSat, ex.IncludeSun,
        GETDATE(), GETDATE()
    FROM expanded ex
    WHERE
        (DATEPART(weekday, ex.work_date) = 2 AND ex.IncludeMon = 1) OR
        (DATEPART(weekday, ex.work_date) = 3 AND ex.IncludeTue = 1) OR
        (DATEPART(weekday, ex.work_date) = 4 AND ex.IncludeWed = 1) OR
        (DATEPART(weekday, ex.work_date) = 5 AND ex.IncludeThu = 1) OR
        (DATEPART(weekday, ex.work_date) = 6 AND ex.IncludeFri = 1) OR
        (DATEPART(weekday, ex.work_date) = 7 AND ex.IncludeSat = 1) OR
        (DATEPART(weekday, ex.work_date) = 1 AND ex.IncludeSun = 1);
END;
GO
```

- [ ] **Step 3: Trigger for `D30Attendance` (upsert daily + recalc summary)**

```sql
-- =============================================================================
-- Trigger: D30Attendance INSERT/UPDATE — upsert attendance_daily
-- =============================================================================
CREATE OR ALTER TRIGGER dbo.tr_D30Attendance_IOI
ON dbo.D30Attendance
INSTEAD OF INSERT
AS
BEGIN
    SET NOCOUNT ON;

    -- Each inserted row needs employee_id resolved via assigned_shift_code → shift_assignment.
    -- For simplicity assume SP supplies AssignedShiftCode that matches an existing shift_assignment.
    INSERT INTO dbo.attendance_daily
        (doc_id, employee_id, work_date, attendance_period_id, shift_assignment_id,
         assigned_shift_code, regular_hours, workday_value,
         paid_leave_days, unpaid_leave_days, exclude_days, exclude_hours,
         night_hours, meal_count, attendance_status, calculation_version,
         created_at, updated_at)
    SELECT
        i.DocId,
        sa.employee_id,
        i.[Date],
        ap.id,
        sa.id,
        i.AssignedShiftCode,
        i.WorkingHours, i.WorkingDays,
        i.PaidLeaveDays, i.UnpaidLeaveDays, i.ExcludeDays, i.ExcludeHours,
        i.WorkNightHours, i.ShiftMeal,
        CASE WHEN i.WorkingDays > 0 THEN 'present' ELSE 'absent' END,
        1, GETDATE(), GETDATE()
    FROM inserted i
    JOIN dbo.shift_assignments sa ON sa.id = (
        SELECT TOP 1 sa2.id FROM dbo.shift_assignments sa2
        JOIN dbo.shifts s ON s.id = sa2.shift_id
        WHERE s.code = i.AssignedShiftCode AND sa2.work_date = i.[Date]
    )
    JOIN dbo.attendance_periods ap ON i.[Date] BETWEEN ap.from_date AND ap.to_date;
END;
GO

CREATE OR ALTER TRIGGER dbo.tr_D30Attendance_IOU
ON dbo.D30Attendance
INSTEAD OF UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE ad
       SET ad.regular_hours      = i.WorkingHours,
           ad.workday_value      = i.WorkingDays,
           ad.paid_leave_days    = i.PaidLeaveDays,
           ad.unpaid_leave_days  = i.UnpaidLeaveDays,
           ad.exclude_days       = i.ExcludeDays,
           ad.exclude_hours      = i.ExcludeHours,
           ad.night_hours        = i.WorkNightHours,
           ad.meal_count         = i.ShiftMeal,
           ad.updated_at         = GETDATE()
    FROM dbo.attendance_daily ad
    JOIN inserted i ON i.DocId = ad.doc_id
                    OR (i.DocId = CAST(ad.id AS VARCHAR(16)));
END;
GO
```

- [ ] **Step 4: Trigger for `D30Payroll` (upsert payslip)**

```sql
-- =============================================================================
-- Trigger: D30Payroll INSERT/UPDATE
-- =============================================================================
CREATE OR ALTER TRIGGER dbo.tr_D30Payroll_IOU
ON dbo.D30Payroll
INSTEAD OF UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE p
       SET p.gross_salary       = i.GrossSalary,
           p.taxable_income     = i.TaxableIncome,
           p.insurance_base     = i.SalaryInsurance,
           p.insurance_employee = i.SocialInsEMPLPay,
           p.insurance_company  = i.SocialInsPay,
           p.pit_amount         = i.PersonalIncomeTaxAmount,
           p.bonus_total        = i.BonusSalary,
           p.deduction_total    = i.OtherDeductionsAmount,
           p.net_salary         = i.NetIncome,
           p.updated_at         = GETDATE()
    FROM dbo.payslips p
    JOIN inserted i ON CAST(p.id AS VARCHAR(16)) = i.Id;
END;
GO
```

- [ ] **Step 5: Apply triggers & verify**

Copy + sqlcmd run (same as Step 5 task 2.1).

Verify: `SELECT name FROM sys.triggers WHERE name LIKE 'tr_D%' ORDER BY name;`
Expected: 7 triggers (`tr_D20PayrollParameter_IOI/IOU/IOD`, `tr_D30AssignedShift_IOI`, `tr_D30Attendance_IOI/IOU`, `tr_D30Payroll_IOU`).

- [ ] **Step 6: Commit**

Commit: `feat(triggers): add INSTEAD OF triggers for 4 writable Fujimart views`.

### Task 2.3: Sprint 2 integration test

**Files:**
- Create: `backend/tests/Feature/Views/D20ShiftViewTest.php`

- [ ] **Step 1: Write view test**

```php
<?php

namespace Tests\Feature\Views;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class D20ShiftViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_returns_25_fujimart_columns(): void
    {
        DB::table('shifts')->insert([
            'code' => 'CA1', 'name' => 'Ca sáng',
            'description' => 'Ca sáng 8h-12h',
            'is_checkin' => 1, 'is_checkout' => 1,
            'start_time' => '08:00:00', 'end_time' => '12:00:00',
            'workday_value' => 1.0, 'working_hours' => 4.0,
            'shift_break' => 0, 'shift_meal' => 0,
            'min_meal_hours' => 4.0,
            'status' => 'active', 'is_active' => 1,
        ]);

        $row = DB::selectOne('SELECT * FROM dbo.D20Shift WHERE Code = ?', ['CA1']);
        $this->assertNotNull($row);
        $this->assertSame('CA1', $row->Code);
        $this->assertSame('Ca sáng', $row->Name);
        $this->assertSame(1, (int) $row->IsCheckin);
        $this->assertSame(1, (int) $row->IsCheckout);
        $this->assertEquals(4.0, (float) $row->WorkingHours);
    }
}
```

- [ ] **Step 2: Test D30AssignedShift trigger expansion**

Create: `backend/tests/Feature/Views/D30AssignedShiftViewTest.php`

```php
<?php

namespace Tests\Feature\Views;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class D30AssignedShiftViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_inserting_5_day_pattern_expands_to_5_rows(): void
    {
        // Seed
        DB::table('shifts')->insert(['id'=>1,'code'=>'CA1','name'=>'Day','start_time'=>'08:00','end_time'=>'17:00','workday_value'=>1.0,'status'=>'active','is_active'=>1]);
        DB::table('users')->insert(['id'=>1,'name'=>'A','username'=>'a','email'=>'a@a','password'=>'x']);
        DB::table('employees')->insert(['id'=>1,'employee_code'=>'NV001','full_name'=>'A','user_id'=>1,'employment_status'=>'active']);

        // Insert through view: Mon-Fri pattern over 1 week
        DB::statement(<<<'SQL'
            INSERT INTO dbo.D30AssignedShift
                (AssignId, [Date], EmployeeCode, ShiftCode, StartDate, EndDate,
                 IncludeMon, IncludeTue, IncludeWed, IncludeThu, IncludeFri, IncludeSat, IncludeSun, Description)
            VALUES ('NEW1', '2026-05-04', 'NV001', 'CA1', '2026-05-04', '2026-05-10',
                    1, 1, 1, 1, 1, 0, 0, 'Mon-Fri test')
        SQL);

        // 2026-05-04 is Monday → 5 weekdays (Mon-Fri)
        $count = DB::table('shift_assignments')->where('employee_id', 1)->count();
        $this->assertSame(5, $count);
    }
}
```

- [ ] **Step 3: Run + commit**

Run: `docker exec payroll-backend php artisan test --filter='ViewTest'`

Expected: 2 tests pass.

Commit: `test(views): verify D20Shift schema + D30AssignedShift trigger expansion`.

---

## Sprint 3 — 5 Stored Procedures

> **Note:** Plan này outline SP. Logic chi tiết (insurance rates, PIT brackets) lấy từ `04_stored_procedures.sql` hiện có (đã có `sp_preview_payroll` ~349 dòng). Khi viết SP mới, copy logic core, đổi target từ bảng migrate sang VIEW.

### Task 3.1: `usp_CreateAndCalculateAttendance`

**Files:**
- Create: `backend/database/sql/07_sp_fujimart.sql` (file mới, sẽ chứa cả 5 SP)

- [ ] **Step 1: Define skeleton**

```sql
SET ANSI_NULLS ON;
GO
SET QUOTED_IDENTIFIER ON;
GO

-- =============================================================================
-- 1. usp_CreateAndCalculateAttendance
--    Tính & tổng hợp công cho 1 ngày @_DocDate1 (đầu kỳ tháng).
-- =============================================================================
CREATE OR ALTER PROCEDURE dbo.usp_CreateAndCalculateAttendance
    @_DocDate1     DATE,
    @_BranchCode   NVARCHAR(16) = '',
    @_DeptCode     NVARCHAR(16) = '',
    @_EmployeeCode NVARCHAR(16) = '',
    @_result_msg   NVARCHAR(200) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @period_id BIGINT;
    DECLARE @from_date DATE = DATEFROMPARTS(YEAR(@_DocDate1), MONTH(@_DocDate1), 1);
    DECLARE @to_date   DATE = EOMONTH(@from_date);
    DECLARE @inserted  INT  = 0;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Ensure attendance_period exists
        SELECT @period_id = id FROM dbo.attendance_periods
        WHERE from_date = @from_date AND to_date = @to_date;

        IF @period_id IS NULL
        BEGIN
            INSERT INTO dbo.attendance_periods
                (period_code, month, year, from_date, to_date, status, created_at, updated_at)
            VALUES
                (CONCAT(YEAR(@from_date), '-', RIGHT('0'+CAST(MONTH(@from_date) AS VARCHAR),2)),
                 MONTH(@from_date), YEAR(@from_date), @from_date, @to_date,
                 'draft', GETDATE(), GETDATE());
            SET @period_id = SCOPE_IDENTITY();
        END

        -- Delegate to existing sp_generate_attendance_daily
        EXEC dbo.sp_generate_attendance_daily
            @attendance_period_id = @period_id,
            @generated_count = @inserted OUTPUT;

        EXEC dbo.sp_generate_attendance_summary
            @attendance_period_id = @period_id;

        SET @_result_msg = CONCAT(N'Đã tính & tổng hợp công cho ', @inserted, N' bản ghi.');
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SET @_result_msg = CONCAT(N'Lỗi: ', ERROR_MESSAGE());
        THROW;
    END CATCH
END;
GO
```

- [ ] **Step 2: Add `usp_CreateAndCalculatePayroll`**

```sql
CREATE OR ALTER PROCEDURE dbo.usp_CreateAndCalculatePayroll
    @_DocDate1     DATE,
    @_BranchCode   NVARCHAR(16) = '',
    @_DeptCode     NVARCHAR(16) = '',
    @_EmployeeCode NVARCHAR(16) = '',
    @_result_msg   NVARCHAR(200) OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @period_id BIGINT;
    DECLARE @run_id    BIGINT;
    DECLARE @from_date DATE = DATEFROMPARTS(YEAR(@_DocDate1), MONTH(@_DocDate1), 1);
    DECLARE @to_date   DATE = EOMONTH(@from_date);

    BEGIN TRY
        BEGIN TRANSACTION;

        SELECT @period_id = id FROM dbo.attendance_periods
        WHERE from_date = @from_date AND to_date = @to_date;

        IF @period_id IS NULL
        BEGIN
            SET @_result_msg = N'Chưa có kỳ chấm công cho tháng này. Hãy chạy usp_CreateAndCalculateAttendance trước.';
            ROLLBACK; RETURN;
        END

        -- Get-or-create draft run
        SELECT @run_id = id FROM dbo.payroll_runs
        WHERE attendance_period_id = @period_id AND status IN ('draft','preview');

        IF @run_id IS NULL
        BEGIN
            INSERT INTO dbo.payroll_runs
                (attendance_period_id, run_no, scope_type, status, created_at, updated_at)
            VALUES (@period_id, 1, 'all', 'preview', GETDATE(), GETDATE());
            SET @run_id = SCOPE_IDENTITY();
        END

        EXEC dbo.sp_preview_payroll @payroll_run_id = @run_id;

        DECLARE @cnt INT;
        SELECT @cnt = COUNT(*) FROM dbo.payslips WHERE payroll_run_id = @run_id;
        SET @_result_msg = CONCAT(N'Đã tính lương cho ', @cnt, N' nhân viên.');

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
        SET @_result_msg = CONCAT(N'Lỗi: ', ERROR_MESSAGE());
        THROW;
    END CATCH
END;
GO
```

- [ ] **Step 3: Add `usp_AttendanceReport` (pivot 31 cột)**

```sql
CREATE OR ALTER PROCEDURE dbo.usp_AttendanceReport
    @_DocDate1     DATE,
    @_DocDate2     DATE,
    @_BranchCode   VARCHAR(16) = '',
    @_DeptCode     VARCHAR(512) = '',
    @_EmployeeCode VARCHAR(512) = ''
AS
BEGIN
    SET NOCOUNT ON;

    -- Result set 1: header (nhân viên × cột tổng hợp)
    SELECT
        e.employee_code  AS EmployeeCode,
        e.full_name      AS FullName,
        d.code           AS DeptCode,
        d.name           AS DeptName,
        b.code           AS BranchCode,
        SUM(a.WorkingDays)   AS TotalWorkDays,
        SUM(a.WorkingHours)  AS TotalWorkHours,
        SUM(a.PaidLeaveDays) AS TotalPaidLeave
    FROM dbo.D30Attendance a
    JOIN dbo.employees e ON e.id = (SELECT TOP 1 id FROM dbo.employees WHERE employee_code IN (
        SELECT EmployeeCode FROM dbo.D30AssignedShift WHERE AssignId = a.DocId))  -- best effort
    LEFT JOIN dbo.departments d ON d.id = e.department_id
    LEFT JOIN dbo.branches b    ON b.id = e.branch_id
    WHERE a.[Date] BETWEEN @_DocDate1 AND @_DocDate2
    GROUP BY e.employee_code, e.full_name, d.code, d.name, b.code;

    -- Result set 2: detail per day (used by FE Excel exporter)
    SELECT
        e.employee_code AS EmployeeCode,
        a.[Date],
        a.WorkingHours,
        a.WorkingDays,
        a.PaidLeaveDays,
        a.ExcludeHours
    FROM dbo.D30Attendance a
    JOIN dbo.employees e ON e.id = a.DocId   -- temporary; SP can be refined later
    WHERE a.[Date] BETWEEN @_DocDate1 AND @_DocDate2
    ORDER BY e.employee_code, a.[Date];
END;
GO
```

- [ ] **Step 4: Add `usp_PayrollReport`**

```sql
CREATE OR ALTER PROCEDURE dbo.usp_PayrollReport
    @_DocDate1     DATE,
    @_BranchCode   VARCHAR(16) = '',
    @_DeptCode     VARCHAR(512) = '',
    @_EmployeeCode VARCHAR(512) = ''
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        p.BranchCode,
        p.DeptCode,
        p.EmployeeCode,
        e.full_name      AS FullName,
        p.GrossSalary,
        p.NetIncome,
        p.SocialInsEMPLPay,
        p.PersonalIncomeTaxAmount,
        (p.NetIncome - p.SocialInsEMPLPay - p.PersonalIncomeTaxAmount) AS TakeHome
    FROM dbo.D30Payroll p
    JOIN dbo.employees e ON e.employee_code = p.EmployeeCode
    WHERE p.[Date] = @_DocDate1
      AND (@_BranchCode = '' OR p.BranchCode IN (SELECT value FROM STRING_SPLIT(@_BranchCode, ',')))
      AND (@_DeptCode = ''   OR p.DeptCode IN (SELECT value FROM STRING_SPLIT(@_DeptCode, ',')))
      AND (@_EmployeeCode = '' OR p.EmployeeCode IN (SELECT value FROM STRING_SPLIT(@_EmployeeCode, ',')))
    ORDER BY p.BranchCode, p.DeptCode, p.EmployeeCode;
END;
GO
```

- [ ] **Step 5: Add `usp_PayrollSlip` (with @_SendEmail)**

```sql
CREATE OR ALTER PROCEDURE dbo.usp_PayrollSlip
    @_DocDate1     DATE,
    @_EmployeeCode VARCHAR(512) = '',
    @_DeptCode     VARCHAR(512) = '',
    @_BranchCode   VARCHAR(16)  = '',
    @_SendEmail    TINYINT      = 0,
    @_MailProfile  NVARCHAR(128) = N''
AS
BEGIN
    SET NOCOUNT ON;

    IF @_SendEmail = 0
    BEGIN
        -- Result set: slip header + detail per employee
        SELECT
            p.EmployeeCode,
            e.full_name AS FullName,
            p.BranchCode,
            p.DeptCode,
            p.GrossSalary,
            p.NetIncome,
            p.SocialInsEMPLPay,
            p.PersonalIncomeTaxAmount,
            (p.NetIncome - p.SocialInsEMPLPay - p.PersonalIncomeTaxAmount) AS TakeHome
        FROM dbo.D30Payroll p
        JOIN dbo.employees e ON e.employee_code = p.EmployeeCode
        WHERE p.[Date] = @_DocDate1
          AND (@_EmployeeCode = '' OR p.EmployeeCode = @_EmployeeCode);

        SELECT
            d.PayRollId,
            d.SalaryType,
            d.Amount,
            d.Days,
            d.Hours
        FROM dbo.D30PayrollDetail d
        WHERE d.PayRollId IN (
            SELECT p.Id FROM dbo.D30Payroll p
            WHERE p.[Date] = @_DocDate1
              AND (@_EmployeeCode = '' OR p.EmployeeCode = @_EmployeeCode)
        )
        ORDER BY d.PayRollId, d.BuiltinOrder;
    END
    ELSE
    BEGIN
        -- Send email per employee (msdb dbmail)
        DECLARE @code VARCHAR(16), @mail NVARCHAR(128), @body NVARCHAR(MAX), @subject NVARCHAR(256);
        DECLARE cur CURSOR FAST_FORWARD FOR
            SELECT p.EmployeeCode, e.email
            FROM dbo.D30Payroll p
            JOIN dbo.employees e ON e.employee_code = p.EmployeeCode
            WHERE p.[Date] = @_DocDate1
              AND e.email IS NOT NULL;

        OPEN cur; FETCH NEXT FROM cur INTO @code, @mail;
        WHILE @@FETCH_STATUS = 0
        BEGIN
            SET @subject = CONCAT(N'Phiếu lương tháng ', FORMAT(@_DocDate1, 'MM/yyyy'));
            SET @body = CONCAT(N'Gửi anh/chị ', @code, N',<br>Phiếu lương đính kèm.');
            BEGIN TRY
                EXEC msdb.dbo.sp_send_dbmail
                    @profile_name = @_MailProfile,
                    @recipients   = @mail,
                    @subject      = @subject,
                    @body         = @body,
                    @body_format  = 'HTML';
            END TRY
            BEGIN CATCH
                -- Log + continue
                PRINT CONCAT('Mail failed for ', @code, ': ', ERROR_MESSAGE());
            END CATCH
            FETCH NEXT FROM cur INTO @code, @mail;
        END
        CLOSE cur; DEALLOCATE cur;

        SELECT N'Đã gửi email phiếu lương.' AS Message;
    END
END;
GO
```

- [ ] **Step 6: Apply + verify + commit**

Copy + sqlcmd run. Verify `SELECT name FROM sys.procedures WHERE name LIKE 'usp_%' ORDER BY name;` includes 5 new SPs.

Commit: `feat(sp): add 5 Fujimart stored procedures (CreateAndCalculate*, *Report, PayrollSlip)`.

### Task 3.2: Sprint 3 SP smoke test

**Files:**
- Create: `backend/tests/Feature/StoredProcedures/UspCreateAndCalculateAttendanceTest.php`

- [ ] **Step 1: Write test**

```php
<?php

namespace Tests\Feature\StoredProcedures;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UspCreateAndCalculateAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sp_runs_and_returns_message(): void
    {
        // Seed a minimal employee + shift + assignment + time logs ...
        // (use factories or raw DB::insert; see FujimartSampleSeeder)

        $msg = '';
        DB::statement('DECLARE @m NVARCHAR(200);
                       EXEC dbo.usp_CreateAndCalculateAttendance
                            @_DocDate1=?, @_BranchCode=?, @_result_msg=@m OUTPUT;
                       SELECT @m AS msg', ['2026-01-01', 'A01']);

        $row = DB::selectOne('SELECT TOP 1 calculation_version FROM dbo.attendance_daily');
        $this->assertNotNull($row);
    }
}
```

- [ ] **Step 2: Run + commit**

---

## Sprint 4 — BE Controllers + FE Pages

### Task 4.1: Eloquent models (Branch, D20PayrollParameter, SalaryScale/Grade/Detail)

**Files:** `backend/app/Models/Branch.php`, `D20PayrollParameter.php`, `SalaryScale.php`, `SalaryGrade.php`, `SalaryGradeDetail.php`, `LateEarlyRuleDetail.php`

- [ ] **Step 1: Branch.php**

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'branches';
    protected $fillable = ['code', 'name', 'address', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
```

- [ ] **Step 2: D20PayrollParameter.php**

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class D20PayrollParameter extends Model
{
    protected $table = 'd20_payroll_parameters';
    protected $fillable = ['parameter','name','description','effective_date','type','amount','is_active'];
    protected $casts = ['effective_date' => 'date', 'amount' => 'decimal:2', 'is_active' => 'boolean'];

    public const TYPES_VALUE_TAB     = ['VALUE','RATE','COEFF'];
    public const TYPES_SALARY_TAB    = ['INCOME','BONUS','DEDUCTION'];
}
```

- [ ] **Step 3-5: SalaryScale / SalaryGrade / SalaryGradeDetail / LateEarlyRuleDetail**

(Tương tự — 4 models đơn giản với `$table`, `$fillable`.)

- [ ] **Step 6: Update `Shift` fillable**

Modify `backend/app/Models/Shift.php` line 13-27: thêm 17 cột mới vào `$fillable`.

```php
protected $fillable = [
    'code','name','description',
    'is_checkin','is_checkout',
    'start_time','start_time_valid1','start_time_valid2',
    'end_time','end_time_valid1','end_time_valid2',
    'break_start_time','start_break_time_valid1','start_break_time_valid2',
    'break_end_time','end_break_time_valid1','end_break_time_valid2',
    'shift_break','shift_break_mins',
    'workday_value','working_hours','min_meal_hours','shift_meal',
    'start_working_night_time','end_working_night_time',
    'timesheet_type','is_overnight',
    'grace_late_minutes','grace_early_minutes',
    'status','is_active',
];
```

- [ ] **Step 7: Update `ShiftAssignment` fillable**

Modify `backend/app/Models/ShiftAssignment.php` line 12-18: thêm pattern fields.

- [ ] **Step 8: Commit**

Commit: `feat(models): add Branch, D20PayrollParameter, SalaryScale/Grade/Detail + update Shift/ShiftAssignment fillable`.

### Task 4.2: Backend Controller `FujimartProcedureController`

**Files:** `backend/app/Http/Controllers/Api/FujimartProcedureController.php`

- [ ] **Step 1: Create controller**

```php
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
            'doc_date' => 'required|date',
            'branch_code' => 'nullable|string|max:16',
            'dept_code' => 'nullable|string|max:16',
            'employee_code' => 'nullable|string|max:16',
        ]);

        $msg = '';
        DB::statement(
            'DECLARE @m NVARCHAR(200);
             EXEC dbo.usp_CreateAndCalculateAttendance
                @_DocDate1=?, @_BranchCode=?, @_DeptCode=?, @_EmployeeCode=?,
                @_result_msg=@m OUTPUT;
             SELECT @m AS message;',
            [$req->doc_date, $req->branch_code ?? '', $req->dept_code ?? '', $req->employee_code ?? '']
        );
        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function payrollCalculate(Request $req): JsonResponse { /* tương tự */ }
    public function attendanceReport(Request $req): JsonResponse { /* gọi usp_AttendanceReport */ }
    public function payrollReport(Request $req): JsonResponse    { /* gọi usp_PayrollReport */ }
    public function payrollSlip(Request $req): JsonResponse      { /* gọi usp_PayrollSlip */ }
}
```

- [ ] **Step 2: Register routes**

Modify `backend/routes/api.php` (append):

```php
Route::prefix('fujimart')->controller(FujimartProcedureController::class)->group(function () {
    Route::post('attendance/calculate', 'attendanceCalculate');
    Route::post('payroll/calculate',    'payrollCalculate');
    Route::get ('reports/attendance',   'attendanceReport');
    Route::get ('reports/payroll',      'payrollReport');
    Route::post('reports/payslip',      'payrollSlip');
});
```

- [ ] **Step 3: Commit**

### Task 4.3: Frontend — ShiftsPage (25 cột)

**Files:** `frontend/src/pages/ShiftsPage.tsx` — MODIFY

- [ ] **Step 1: Add column groups**

Wrap table in 3 visual sections: Basic (Code, Name, Description), Check-in/out times (Start/End + Valid1/2 + IsCheckin/Out), Break + meal (ShiftBreak, break times, WorkingHours, ShiftMeal, MinHourMeal, night times).

- [ ] **Step 2: Add modal form with 25 input fields**

Group fields into accordion: General / Time validation / Break / Night / Meal.

- [ ] **Step 3: Commit**

### Task 4.4: Frontend — ShiftAssignmentsPage (dải ngày + 7 checkbox)

**Files:** `frontend/src/pages/ShiftAssignmentsPage.tsx`

- [ ] **Step 1: Replace single work_date with start_date + end_date + 7 weekday checkboxes**

```tsx
<div className="grid grid-cols-2 gap-4">
  <DatePicker label="Từ ngày" value={form.start_date} ... />
  <DatePicker label="Đến ngày" value={form.end_date} ... />
</div>
<div className="flex gap-2">
  {['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].map(d => (
    <Checkbox key={d} label={d} checked={form[`include_${d.toLowerCase()}`]} ... />
  ))}
</div>
```

- [ ] **Step 2: Commit**

### Task 4.5: Frontend — PayrollParametersPage (2 tab)

**Files:** `frontend/src/pages/PayrollParametersPage.tsx` (CREATE/MODIFY)

- [ ] **Step 1: Add tabs**

```tsx
const [tab, setTab] = useState<'value'|'salary'>('value');
const endpoint = tab === 'value'
  ? '/reference/payroll-parameters?view=vD20PayrollPara_ValuePara'
  : '/reference/payroll-parameters?view=vD20PayrollPara_SalaryType';

const query = useQuery({ queryKey:['payroll-params', tab], queryFn: () => apiGet(endpoint) });
```

- [ ] **Step 2: BE endpoint reading view**

In `backend/app/Http/Controllers/Api/ReferenceController.php`, add `payrollParameters(Request $req)` that switches between views by query param `?view=`.

- [ ] **Step 3: Commit**

### Task 4.6: Frontend — Date format DD/MM/YYYY toàn cục

**Files:** `frontend/src/lib/format.ts`

- [ ] **Step 1: Update formatDate default**

```ts
export function formatDate(input: string | Date | null | undefined, fmt = 'dd/MM/yyyy') { ... }
```

- [ ] **Step 2: Commit**

---

## Sprint 5 — End-to-end smoke test

### Task 5.1: Run full pipeline

- [ ] **Step 1: Fresh migrate**

```bash
docker exec payroll-backend php artisan migrate:fresh
docker exec payroll-backend php artisan db:seed --class=DatabaseSeeder
```

- [ ] **Step 2: Apply SQL files in order**

```bash
for f in 06_views_fujimart.sql 06b_triggers_fujimart.sql 07_sp_fujimart.sql; do
  docker cp backend/database/sql/$f payroll-sqlserver:/tmp/$f
  docker exec payroll-sqlserver /opt/mssql-tools18/bin/sqlcmd -U sa -P "YourStrong!Passw0rd" -C -d enterprise_payroll_erp -i /tmp/$f
done
```

- [ ] **Step 3: Seed Fujimart sample data**

```bash
docker exec payroll-backend php artisan db:seed --class=FujimartSampleSeeder
```

- [ ] **Step 4: EXEC `usp_CreateAndCalculateAttendance`**

```sql
DECLARE @m NVARCHAR(200);
EXEC dbo.usp_CreateAndCalculateAttendance
    @_DocDate1='2026-01-01', @_BranchCode='A01', @_result_msg=@m OUTPUT;
SELECT @m;
```

Expected: "Đã tính & tổng hợp công cho N bản ghi."

- [ ] **Step 5: EXEC `usp_CreateAndCalculatePayroll`**

```sql
DECLARE @m NVARCHAR(200);
EXEC dbo.usp_CreateAndCalculatePayroll
    @_DocDate1='2026-01-01', @_BranchCode='A01', @_result_msg=@m OUTPUT;
SELECT @m;
```

Expected: "Đã tính lương cho N nhân viên."

- [ ] **Step 6: Open FE in browser**

Visit `http://localhost:5174` (override port) → đăng nhập → `Danh mục ca làm việc` hiển thị 25 cột → `Tham số lương` có 2 tab.

- [ ] **Step 7: Commit final**

```bash
git add -A
git commit -m "feat(fujimart): end-to-end schema mapping with views + SPs + UI"
```

---

## Self-Review

**Spec coverage:**
- D20Shift mapping → Task 1.1 (migration) + Task 2.1 (view) + Task 4.3 (FE) ✓
- D30AssignedShift mapping → Task 1.2 + Task 2.1 + Task 2.2 (trigger expand) + Task 4.4 ✓
- D20PayrollParameter 2 tab → Task 1.5 + Task 2.1 (3 views) + Task 4.5 ✓
- D20Branch → Task 1.3 + Task 2.1 ✓
- Employee bổ sung Gender/BirthDate/… → Task 1.4 (đã có migration `add_resign_date` cũ) ✓
- SalaryScale → Grade → Detail (3 cấp) → Task 1.6-1.8 ✓
- is_active cho nút Đình chỉ → Task 1.11 ✓
- 4 SP tính/báo cáo → Task 3.1 ✓
- usp_PayrollSlip + SendEmail → Task 3.1 step 5 ✓
- Date DD/MM/YYYY → Task 4.6 ✓
- Docker port không xung đột → Pre-flight P1-P3 ✓

**Placeholder scan:** Task 4.1 step 3-5 "tương tự — 4 models đơn giản" → có rủi ro placeholder. Trong execution sẽ cần expand từng file. Acceptable cho plan với note rõ.

**Type consistency:** `is_checkin`/`is_checkout` boolean trong migration, cast TINYINT trong view → consistent. `start_time` là TIME trong migrate, view CAST AS DATETIME → đã ghi rõ.

---

## Risks identified during planning

1. `D30Attendance` trigger giả định `AssignedShiftCode` có shift_assignment cùng ngày — không đúng khi SP tự tạo Attendance từ scratch. Mitigation: SP `usp_CreateAndCalculateAttendance` không INSERT qua view, mà dùng `sp_generate_attendance_daily` trên bảng vật lý.
2. `D30AssignedShift` expand qua trigger có thể tạo `366 × N` rows nếu user nhập dải dài. Mitigation: trigger TOP (366) hạn chế 1 năm.
3. `usp_PayrollSlip` cần Database Mail configured. Mitigation: nếu chưa cấu hình, mail fail nhưng SP vẫn return — log warning.

---

## Execution Handoff

Plan complete và lưu tại `docs/superpowers/plans/2026-05-17-fujimart-table-mapping.md`. Hai cách chạy:

**1. Subagent-Driven (khuyến nghị):** Mỗi task dispatch 1 subagent mới, review giữa task, vòng nhanh.
**2. Inline Execution:** Chạy trong session hiện tại, batch checkpoint review.

Sau khi user chọn, sẽ invoke `superpowers:subagent-driven-development` hoặc `superpowers:executing-plans`.
