<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_withholding_tax', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('month'); // 1–12
            $table->smallInteger('year');
            $table->string('staff_name');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('rate', 5, 4)->default(0.0750); // 7.5%
            $table->decimal('wht_amount', 12, 2)->default(0); // auto-computed
            $table->timestamps();

            $table->index(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_withholding_tax');
    }
};
