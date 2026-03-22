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
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->dateTime('log_time');
            $table->string('machine_number', 20)->nullable();
            $table->string('log_type', 10)->default('unknown');
            $table->string('source', 20)->default('machine');
            $table->boolean('is_valid')->default(true);
            $table->string('invalid_reason', 200)->nullable();
            $table->string('raw_ref', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['employee_id', 'log_time']);
            $table->index('log_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
