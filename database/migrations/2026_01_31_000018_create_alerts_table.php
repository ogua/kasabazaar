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
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->nullable();
            $table->uuid('user_id')->nullable();

            $table->string('alert_type');
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('related_model')->nullable();
            $table->uuid('related_id')->nullable();

            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
