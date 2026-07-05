<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_annual_statements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investor_id');
            $table->unsignedSmallInteger('year');
            $table->text('business_updates')->nullable();
            $table->uuid('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('investor_id');
            $table->unique(['investor_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_annual_statements');
    }
};
