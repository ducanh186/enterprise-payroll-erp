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
        Schema::create('bonus_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('attendance_period_id');
            $table->unsignedBigInteger('type_id');
            $table->decimal('amount', 18, 2);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('attendance_period_id')->references('id')->on('attendance_periods');
            $table->foreign('type_id')->references('id')->on('bonus_deduction_types');
            $table->index(['attendance_period_id', 'employee_id'], 'bd_period_employee_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_deductions');
    }
};
