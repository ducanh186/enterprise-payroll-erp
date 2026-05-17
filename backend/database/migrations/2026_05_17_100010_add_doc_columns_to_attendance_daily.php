<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_daily', function (Blueprint $table) {
            $table->string('doc_id', 16)->nullable()->after('id');
            $table->string('assigned_shift_code', 16)->nullable()->after('shift_assignment_id');
            $table->decimal('paid_leave_days', 8, 2)->default(0)->after('night_hours');
            $table->decimal('unpaid_leave_days', 8, 2)->default(0)->after('paid_leave_days');
            $table->decimal('exclude_days', 8, 2)->default(0)->after('unpaid_leave_days');
            $table->decimal('exclude_hours', 8, 2)->default(0)->after('exclude_days');
        });

        // SQL Server filtered unique index (skip NULL rows)
        DB::statement('CREATE UNIQUE INDEX attendance_daily_doc_id_unique
                       ON attendance_daily (doc_id) WHERE doc_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX attendance_daily_doc_id_unique ON attendance_daily');

        Schema::table('attendance_daily', function (Blueprint $table) {
            $table->dropColumn(['doc_id', 'assigned_shift_code', 'paid_leave_days',
                                'unpaid_leave_days', 'exclude_days', 'exclude_hours']);
        });
    }
};
