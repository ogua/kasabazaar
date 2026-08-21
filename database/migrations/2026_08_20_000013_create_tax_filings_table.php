<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_filings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('year');
            $table->string('filing_type'); // TaxFilingType enum value
            $table->date('filed_at')->nullable();
            $table->string('reference')->nullable();
            $table->string('document_path')->nullable();
            $table->uuid('uploaded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['year', 'filing_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_filings');
    }
};
