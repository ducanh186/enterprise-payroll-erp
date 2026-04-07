<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique()->comment('URL-friendly code, e.g. attendance-report');
            $table->string('label', 200)->comment('Display name, e.g. Bảng chấm công');
            $table->string('procedure_name', 200)->comment('Full SP name, e.g. dbo.usp_Hrm_AttendanceReport');
            $table->string('module', 50)->default('general')->comment('Module grouping: attendance, payroll, hr');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('procedure_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained('procedure_catalog')->cascadeOnDelete();
            $table->string('name', 100)->comment('Parameter name as sent from FE');
            $table->string('sp_param_name', 100)->comment('Actual SP parameter name, e.g. @_DocDate1');
            $table->string('type', 20)->comment('date, integer, string, tinyint, boolean');
            $table->string('label', 200)->nullable()->comment('Display label in Vietnamese');
            $table->boolean('required')->default(false);
            $table->string('default_value', 200)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['procedure_id', 'name']);
        });

        Schema::create('procedure_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained('procedure_catalog')->cascadeOnDelete();
            $table->string('key', 100)->comment('Column key from SP result set');
            $table->string('label', 200)->comment('Display header label');
            $table->string('type', 20)->default('string')->comment('string, number, date, boolean');
            $table->boolean('visible')->default(true)->comment('Show in FE table');
            $table->boolean('exportable')->default(true)->comment('Include in Excel export');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['procedure_id', 'key']);
        });

        Schema::create('procedure_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained('procedure_catalog')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('parameters')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('execution_ms')->default(0);
            $table->string('status', 20)->default('success')->comment('success, error');
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_execution_logs');
        Schema::dropIfExists('procedure_columns');
        Schema::dropIfExists('procedure_parameters');
        Schema::dropIfExists('procedure_catalog');
    }
};
