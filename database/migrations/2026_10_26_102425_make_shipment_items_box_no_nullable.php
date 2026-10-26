<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shipment wizard saves items without a box number, and the live and dev
 * databases already allow that; this brings the migrations (and so fresh and test
 * databases) in line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->string('box_no')->nullable()->change();
        });
    }

    /**
     * Left nullable: items saved without a box number would block the column
     * becoming required again.
     */
    public function down(): void
    {
        //
    }
};
