<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->uuid('shipment_id')->nullable()->change();
            $table->string('container_number')->nullable()->after('shipment_id');
            $table->string('expense_for')->default('shipment')->after('container_number');

            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->index('container_number');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
            $table->dropIndex(['container_number']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['container_number', 'expense_for']);
            $table->uuid('shipment_id')->nullable(false)->change();
            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
        });
    }
};
