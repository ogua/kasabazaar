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
        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->nullable();

            $table->string('metric');
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly']);
            $table->integer('year');
            $table->integer('period');
            $table->decimal('target_value', 15, 2);
            $table->decimal('achieved_value', 15, 2)->default(0);
            $table->decimal('achievement_percentage', 5, 2)->default(0);

            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
    }
};
