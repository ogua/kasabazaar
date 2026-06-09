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
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->unique(['payroll_period_id', 'staff_id'], 'payroll_entries_period_staff_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropUnique('payroll_entries_period_staff_unique');
        });
    }
};
