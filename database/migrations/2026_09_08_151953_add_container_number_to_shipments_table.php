<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `shipments.container_number` has long existed in the running databases but
     * was never captured as a migration, so fresh installs (and the test DB)
     * lacked it. Add it idempotently.
     */
    public function up(): void
    {
        if (Schema::hasColumn('shipments', 'container_number')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('container_number')->nullable()->after('client_sequence');
            $table->index('container_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('shipments', 'container_number')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['container_number']);
            $table->dropColumn('container_number');
        });
    }
};
