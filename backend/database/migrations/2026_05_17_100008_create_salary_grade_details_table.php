<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_grade_details', function (Blueprint $table) {
            $table->id();
            $table->string('row_id', 32)->unique();
            $table->foreignId('salary_grade_id')->constrained('salary_grades')->cascadeOnDelete();
            $table->integer('salary_type');
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('description', 256)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_grade_details');
    }
};
