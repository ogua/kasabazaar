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
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->uuid('generated_by');

            $table->string('report_type');
            $table->string('title');
            $table->json('parameters')->nullable();
            $table->json('data')->nullable();

            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            $table->string('file_path')->nullable();
            $table->timestamp('generated_at');

            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('generated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
