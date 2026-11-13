<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            // Existing listings default to "new": nothing recorded a condition before.
            $table->string('condition', 20)->default('new')->after('description')->index();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropIndex(['condition']);
            $table->dropColumn('condition');
        });
    }
};
