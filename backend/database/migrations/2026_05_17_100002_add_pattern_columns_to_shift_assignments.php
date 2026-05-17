<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        if (DB::table('shift_assignments')->exists()) {
            DB::statement(<<<'SQL'
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
