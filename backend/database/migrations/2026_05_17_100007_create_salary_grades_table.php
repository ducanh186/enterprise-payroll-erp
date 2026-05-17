<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_grades', function (Blueprint $table) {
            $table->id();
            $table->string('scale_code', 24);
            $table->date('effective_date');
            $table->integer('salary_level')->default(1);
            $table->string('description', 256)->nullable();
            $table->timestamps();
            $table->foreign('scale_code')->references('code')->on('salary_scales')->cascadeOnDelete();
            $table->unique(['scale_code', 'salary_level', 'effective_date'], 'sg_scale_level_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_grades');
    }
};
