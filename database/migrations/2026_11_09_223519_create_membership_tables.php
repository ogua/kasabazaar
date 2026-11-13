<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tier', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_ghs', 10, 2);
            $table->unsignedSmallInteger('duration_months')->default(12);
            $table->decimal('product_discount_percent', 5, 2)->default(0);
            $table->boolean('exclusive_auctions')->default(false);
            $table->json('perks')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('membership_plan_id')->constrained('membership_plans')->restrictOnDelete();
            // pending: awaiting dues; active: paid and running; expired / cancelled: no benefits.
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('amount_ghs', 10, 2);
            $table->string('payment_gateway', 20)->nullable();
            $table->string('payment_reference')->nullable()->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('auctions', function (Blueprint $table) {
            // Lowest membership tier allowed to bid; null leaves the lot open to every buyer.
            $table->string('members_only_tier', 20)->nullable()->after('status');
        });

        Schema::table('ecommerce_order_groups', function (Blueprint $table) {
            $table->decimal('membership_discount_ghs', 10, 2)->default(0)->after('discount_ghs');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_groups', function (Blueprint $table) {
            $table->dropColumn('membership_discount_ghs');
        });

        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn('members_only_tier');
        });

        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_plans');
    }
};
