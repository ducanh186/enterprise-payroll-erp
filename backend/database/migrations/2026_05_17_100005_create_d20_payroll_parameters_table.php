<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('d20_payroll_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter', 64)->unique();
            $table->string('name', 128);
            $table->string('description', 128)->nullable();
            $table->date('effective_date');
            $table->string('type', 32);
            $table->decimal('amount', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('d20_payroll_parameters');
    }
};
