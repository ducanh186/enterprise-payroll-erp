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
            if (Schema::hasColumn('employees', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
            foreach (['nationality', 'address', 'first_working_date'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
