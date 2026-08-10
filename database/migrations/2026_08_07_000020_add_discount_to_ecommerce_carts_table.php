<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_carts', function (Blueprint $table) {
            $table->decimal('discount_ghs', 10, 2)->default(0)->after('coupon_code');
            $table->string('session_id')->nullable()->after('user_id')->index();
        });

        Schema::table('ecommerce_carts', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_carts', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable(false)->change();
        });

        Schema::table('ecommerce_carts', function (Blueprint $table) {
            $table->dropColumn(['discount_ghs', 'session_id']);
        });
    }
};
