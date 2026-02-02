<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_period_id');
            $table->uuid('staff_id');

            // Earnings
            $table->decimal('base_salary', 12, 2);
            $table->decimal('overtime', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);

            // Deductions
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('ssnit', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);

            // Net
            $table->decimal('gross_pay', 12, 2);
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_salary', 12, 2);

            // Status
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->date('paid_at')->nullable();
            $table->string('payment_reference')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
    }
};
