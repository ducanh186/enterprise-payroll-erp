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
