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
        Schema::create('container_clearances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('container_number');
            $table->string('container_year', 2)->nullable();
            $table->foreignUuid('clearing_agent_id')->constrained('clearing_agents')->cascadeOnDelete();
            $table->boolean('is_cleared')->default(true);
            $table->text('review')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();

            $table->index(['container_number', 'cleared_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('container_clearances');
    }
};
