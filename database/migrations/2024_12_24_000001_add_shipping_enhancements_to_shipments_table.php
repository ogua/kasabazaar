<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Insurance amount (add if not exists)
            if (!Schema::hasColumn('shipments', 'insurance')) {
                $table->decimal('insurance', 10, 2)->default(0);
            }
        });

        Schema::table('shipments', function (Blueprint $table) {
            // VAT amount
            if (!Schema::hasColumn('shipments', 'vat')) {
                $table->decimal('vat', 10, 2)->default(0)->after('insurance');
            }

            // VAT percentage
            if (!Schema::hasColumn('shipments', 'vat_percentage')) {
                $table->decimal('vat_percentage', 5, 2)->default(0)->after('vat');
            }

            // Insurance acceptance flag
            if (!Schema::hasColumn('shipments', 'insurance_accepted')) {
                $table->boolean('insurance_accepted')->default(false)->after('vat_percentage');
            }

            // External form token for client self-service
            if (!Schema::hasColumn('shipments', 'external_token')) {
                $table->string('external_token')->nullable()->unique()->after('insurance_accepted');
            }

            // External form completion status
            if (!Schema::hasColumn('shipments', 'external_form_completed')) {
                $table->boolean('external_form_completed')->default(false)->after('external_token');
            }

            // Total clients served this year (for reference generation)
            if (!Schema::hasColumn('shipments', 'client_sequence')) {
                $table->integer('client_sequence')->nullable()->after('external_form_completed');
            }

            // Total shipments made (for reference generation)
            if (!Schema::hasColumn('shipments', 'total_shipment_sequence')) {
                $table->integer('total_shipment_sequence')->nullable()->after('client_sequence');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $columns = [
                'insurance',
                'vat',
                'vat_percentage',
                'insurance_accepted',
                'external_token',
                'external_form_completed',
                'client_sequence',
                'total_shipment_sequence',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
