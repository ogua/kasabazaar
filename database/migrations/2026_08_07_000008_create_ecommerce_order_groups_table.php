<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_order_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_group_number')->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('subtotal_ghs', 10, 2);
            $table->decimal('shipping_fee_ghs', 10, 2)->default(0);
            $table->decimal('discount_ghs', 10, 2)->default(0);
            $table->decimal('tax_ghs', 10, 2)->default(0);
            $table->decimal('total_ghs', 10, 2);
            $table->decimal('total_usd', 10, 2)->nullable();
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('payment_gateway')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('coupon_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'payment_status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_groups');
    }
};
