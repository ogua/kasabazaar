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
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->nullable();

            $table->string('category');
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly']);
            $table->integer('year');
            $table->integer('period');
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('variance', 15, 2)->default(0);
            $table->decimal('variance_percentage', 5, 2)->default(0);

            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
