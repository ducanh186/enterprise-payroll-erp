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
        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->onDelete('cascade');
            $table->string('item_code', 30);
            $table->string('item_name', 100);
            $table->string('item_group', 20);
            $table->decimal('qty', 8, 2)->nullable();
            $table->decimal('rate', 18, 2)->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->string('source_ref', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
    }
};
