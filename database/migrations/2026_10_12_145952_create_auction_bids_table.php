<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount_ghs', 12, 2);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['auction_id', 'amount_ghs']);
            $table->index(['user_id', 'auction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_bids');
    }
};
