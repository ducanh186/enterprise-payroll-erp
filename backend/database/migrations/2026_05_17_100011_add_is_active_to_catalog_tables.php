<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = [
        'shifts',
        'holidays',
        'late_early_rules',
        'contract_types',
        'payroll_types',
        'salary_levels',
        'positions',
        'allowance_types',
        'bonus_deduction_types',
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
