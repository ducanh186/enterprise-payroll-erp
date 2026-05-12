<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'resign_date')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->date('resign_date')->nullable()->after('join_date');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('employees', 'resign_date')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('resign_date');
        });
    }
};
