<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace auction lots. A lot is deliberately NOT an ecommerce_products
 * row: it is sold once, to the highest bidder, and must never be buyable from
 * the catalogue at the same time. When an auction closes with a winner, the
 * win becomes a single-item ecommerce order (ecommerce_product_id = null) at
 * the winning price — see AuctionService::checkout().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('ecommerce_categories')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('condition')->nullable();
            $table->json('image_paths')->nullable();

            $table->decimal('starting_price_ghs', 12, 2);
            // Never exposed through the API — bidders only learn whether it was met.
            $table->decimal('reserve_price_ghs', 12, 2)->nullable();
            $table->decimal('bid_increment_ghs', 12, 2)->default(10);
            $table->decimal('current_price_ghs', 12, 2)->nullable();
            $table->unsignedInteger('bids_count')->default(0);

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('original_ends_at')->nullable();
            // A bid placed inside this window pushes ends_at out by the same amount (anti-sniping).
            $table->unsignedSmallInteger('extension_minutes')->default(2);

            $table->string('status')->default('draft');
            $table->string('outcome')->nullable();
            $table->uuid('winning_bid_id')->nullable();
            $table->foreignUuid('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('payment_due_at')->nullable();
            $table->foreignUuid('order_group_id')->nullable()->constrained('ecommerce_order_groups')->nullOnDelete();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
