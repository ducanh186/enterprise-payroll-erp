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
        Schema::create('labour_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('contract_no', 30)->unique();
            $table->unsignedBigInteger('contract_type_id');
            $table->string('position_title_snapshot', 100)->nullable();
            $table->string('department_snapshot', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('sign_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('base_salary', 18, 2)->default(0);
            $table->unsignedBigInteger('salary_level_id')->nullable();
            $table->unsignedBigInteger('payroll_type_id');
            $table->decimal('probation_rate', 5, 2)->default(100.00);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('contract_type_id')->references('id')->on('contract_types');
            $table->foreign('salary_level_id')->references('id')->on('salary_levels');
            $table->foreign('payroll_type_id')->references('id')->on('payroll_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labour_contracts');
    }
};
