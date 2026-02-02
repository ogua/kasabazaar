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
        Schema::create('exchange_rate_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('from_currency', 3)->default('USD');
            $table->string('to_currency', 3)->default('GHS');
            $table->decimal('rate', 10, 4);
            $table->date('rate_date');
            $table->string('source')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->timestamps();

            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['from_currency', 'to_currency', 'rate_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_logs');
    }
};
