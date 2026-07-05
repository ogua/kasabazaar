<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_rate_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investment_id');
            $table->unsignedSmallInteger('year');
            $table->decimal('annual_rate', 5, 2);
            $table->uuid('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('investment_id');
            $table->unique(['investment_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_rate_overrides');
    }
};
