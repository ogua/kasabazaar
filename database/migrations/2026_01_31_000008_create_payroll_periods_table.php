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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date');
            $table->enum('status', ['draft', 'processing', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->uuid('branch_id');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
