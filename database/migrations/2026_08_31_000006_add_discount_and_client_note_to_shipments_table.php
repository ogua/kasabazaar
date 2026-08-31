<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shipment create wizard collects `discount` (feeds the total calc) and
 * `client_note`, and several services write `client_note`, but no migration ever
 * added the columns — they existed only where they had been added to the DB by
 * hand. This makes them real everywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('vat');
            }

            if (! Schema::hasColumn('shipments', 'client_note')) {
                $table->text('client_note')->nullable()->after('discount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['discount', 'client_note']);
        });
    }
};
