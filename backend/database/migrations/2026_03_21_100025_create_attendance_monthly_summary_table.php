<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_monthly_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->decimal('total_workdays', 4, 1)->default(0);
            $table->decimal('regular_hours', 6, 1)->default(0);
            $table->decimal('ot_hours', 5, 1)->default(0);
            $table->decimal('night_hours', 5, 1)->default(0);
            $table->decimal('paid_leave_days', 4, 1)->default(0);
            $table->decimal('unpaid_leave_days', 4, 1)->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_minutes')->default(0);
            $table->integer('meal_count')->default(0);
            $table->string('status', 30)->default('generated');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['attendance_period_id', 'employee_id'], 'ams_period_employee_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_monthly_summary');
    }
};
