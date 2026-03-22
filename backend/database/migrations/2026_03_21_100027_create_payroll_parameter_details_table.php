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
        Schema::create('payroll_parameter_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_parameter_id')->constrained()->onDelete('cascade');
            $table->string('param_key', 50);
            $table->string('param_type', 20);
            $table->string('default_value', 100)->nullable();
            $table->string('validation_rule', 200)->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_parameter_details');
    }
};
