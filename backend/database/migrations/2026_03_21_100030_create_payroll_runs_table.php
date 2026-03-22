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
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_period_id');
            $table->integer('run_no')->default(1);
            $table->string('scope_type', 20)->default('all');
            $table->string('scope_value', 50)->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('previewed_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamps();

            $table->foreign('attendance_period_id')->references('id')->on('attendance_periods');
            $table->index(['attendance_period_id', 'status'], 'pr_period_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
