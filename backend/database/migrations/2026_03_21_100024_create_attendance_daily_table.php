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
        Schema::create('attendance_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('work_date');
            $table->foreignId('attendance_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_assignment_id')->nullable()->constrained('shift_assignments');
            $table->dateTime('first_in')->nullable();
            $table->dateTime('last_out')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('early_minutes')->default(0);
            $table->decimal('regular_hours', 4, 1)->default(0);
            $table->decimal('ot_hours', 4, 1)->default(0);
            $table->decimal('night_hours', 4, 1)->default(0);
            $table->decimal('workday_value', 3, 1)->default(0);
            $table->integer('meal_count')->default(0);
            $table->string('attendance_status', 20)->default('absent');
            $table->string('source_status', 20)->nullable();
            $table->boolean('is_confirmed_by_employee')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->integer('calculation_version')->default(1);
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
            $table->index(['attendance_period_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_daily');
    }
};
