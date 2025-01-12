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
        Schema::create('quotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id'); // Sender
            $table->uuid('branch_id'); // Origin branch
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->uuid('recorderd_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
