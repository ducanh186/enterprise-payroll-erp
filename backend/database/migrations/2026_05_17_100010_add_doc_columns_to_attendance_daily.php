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

        // Note: filtered unique index intentionally skipped — incompatible with
        // legacy SPs created with QUOTED_IDENTIFIER OFF. Uniqueness is enforced
        // at the application layer (Eloquent validation + service guards).
    }

    public function down(): void
    {
        Schema::table('attendance_daily', function (Blueprint $table) {
            $table->dropColumn(['doc_id', 'assigned_shift_code', 'paid_leave_days',
                                'unpaid_leave_days', 'exclude_days', 'exclude_hours']);
        });
    }
};
