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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_period_id');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('payroll_run_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->decimal('base_salary_snapshot', 18, 2)->default(0);
            $table->decimal('gross_salary', 18, 2)->default(0);
            $table->decimal('taxable_income', 18, 2)->default(0);
            $table->decimal('insurance_base', 18, 2)->default(0);
            $table->decimal('insurance_employee', 18, 2)->default(0);
            $table->decimal('insurance_company', 18, 2)->default(0);
            $table->decimal('pit_amount', 18, 2)->default(0);
            $table->decimal('bonus_total', 18, 2)->default(0);
            $table->decimal('deduction_total', 18, 2)->default(0);
            $table->decimal('net_salary', 18, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->foreign('attendance_period_id')->references('id')->on('attendance_periods');
            $table->foreign('contract_id')->references('id')->on('labour_contracts');
            $table->unique(['payroll_run_id', 'employee_id'], 'payslips_run_employee_unique');
            $table->index(['attendance_period_id', 'employee_id'], 'payslips_period_employee_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
