<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_id')->unique()->constrained('vendors')->cascadeOnDelete();
            $table->decimal('balance_ghs', 12, 2)->default(0);
            $table->decimal('pending_balance_ghs', 12, 2)->default(0);
            $table->decimal('lifetime_earnings_ghs', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_wallets');
    }
};
