<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * target_type becomes a string so new audiences (investors, staff) don't
     * need an enum migration each time; ShipmentMessage::TARGET_TYPES is the
     * source of truth for the allowed values.
     */
    public function up(): void
    {
        Schema::table('shipment_messages', function (Blueprint $table) {
            $table->string('target_type', 32)->default('client')->change();
            $table->uuid('investor_id')->nullable()->after('client_id');
            $table->uuid('staff_id')->nullable()->after('investor_id');

            $table->foreign('investor_id')->references('id')->on('investors')->nullOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_messages', function (Blueprint $table) {
            $table->dropForeign(['investor_id']);
            $table->dropForeign(['staff_id']);
            $table->dropColumn(['investor_id', 'staff_id']);
            $table->enum('target_type', ['client', 'shipment', 'container', 'all'])->default('client')->change();
        });
    }
};
