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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 20)->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->onDelete('cascade');
            $table->string('full_name', 100);
            $table->date('dob')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('national_id', 20)->nullable()->unique();
            $table->string('tax_code', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('bank_account_no', 30)->nullable();
            $table->string('bank_name', 100)->nullable();
            // Avoid SQL Server multiple cascade paths: employees can be linked to
            // both department directly and via position->department.
            $table->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('position_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->date('join_date')->nullable();
            $table->string('employment_status', 20)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
