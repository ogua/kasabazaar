<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            // Stock held in the USA: the only way to receive it in Ghana is KASAROSE USA-Ghana shipping.
            $table->boolean('ships_from_usa')->default(false)->after('weight');
        });

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->string('delivery_method', 30)->default('delivery')->after('shipping_fee_ghs');
            $table->decimal('shipping_weight_kg', 9, 3)->nullable()->after('delivery_method');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_method', 'shipping_weight_kg']);
        });

        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropColumn('ships_from_usa');
        });
    }
};
