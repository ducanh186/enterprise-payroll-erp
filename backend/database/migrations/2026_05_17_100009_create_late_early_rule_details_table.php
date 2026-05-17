<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('late_early_rule_details', function (Blueprint $table) {
            $table->id();
            $table->string('row_id', 16)->unique();
            $table->foreignId('late_early_rule_id')->constrained('late_early_rules')->cascadeOnDelete();
            $table->string('description', 256)->nullable();
            $table->integer('start_minute')->default(0);
            $table->integer('end_minute')->default(0);
            $table->decimal('exclude_time', 8, 2)->default(0);
            $table->decimal('exclude_workday', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_early_rule_details');
    }
};
