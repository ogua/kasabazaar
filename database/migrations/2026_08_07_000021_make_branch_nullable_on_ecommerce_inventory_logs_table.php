<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_inventory_logs', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_inventory_logs', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable(false)->change();
        });
    }
};
