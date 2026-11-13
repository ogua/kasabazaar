<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The company's pickup stations and warehouses, in Ghana and the USA. A station can be
        // where customers collect orders, where vendors deliver items for inspection, or both.
        Schema::create('pickup_stations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('country', 60)->index();
            $table->string('region')->nullable();
            $table->string('city');
            $table->string('address');
            $table->string('phone', 40)->nullable();
            $table->string('opening_hours')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_pickup_point')->default(true);
            $table->boolean('is_warehouse')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            // Where the customer chose to collect a Pickup order.
            $table->foreignUuid('pickup_station_id')->nullable()->after('delivery_method')->constrained('pickup_stations')->nullOnDelete();

            // Vendors never deliver to buyers: items go to our warehouse, are inspected, then continue.
            $table->foreignUuid('warehouse_id')->nullable()->after('delivery_plan_notes')->constrained('pickup_stations')->nullOnDelete();
            $table->timestamp('sent_to_warehouse_at')->nullable()->after('warehouse_id');
            $table->string('vendor_dispatch_note')->nullable()->after('sent_to_warehouse_at');
            $table->timestamp('received_at_warehouse_at')->nullable()->after('vendor_dispatch_note');
            $table->timestamp('inspected_at')->nullable()->after('received_at_warehouse_at');
            $table->foreignUuid('inspected_by')->nullable()->after('inspected_at')->constrained('users')->nullOnDelete();
            $table->string('inspection_result', 10)->nullable()->after('inspected_by'); // passed | failed
            $table->text('inspection_notes')->nullable()->after('inspection_result');
            $table->timestamp('ready_for_pickup_at')->nullable()->after('inspection_notes');
            $table->timestamp('returned_to_vendor_at')->nullable()->after('ready_for_pickup_at');
        });

        // Every return comes to our warehouse and is checked before it goes back to the vendor.
        Schema::create('ecommerce_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('return_number')->unique();
            $table->foreignUuid('order_id')->constrained('ecommerce_orders')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignUuid('warehouse_id')->nullable()->constrained('pickup_stations')->nullOnDelete();
            // requested | approved | received | inspected | returned_to_vendor | refunded | rejected
            $table->string('status', 24)->default('requested')->index();
            $table->string('reason', 500);
            $table->text('details')->nullable();
            $table->text('staff_notes')->nullable();
            $table->string('inspection_result', 20)->nullable(); // as_described | damaged | not_as_described
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamp('returned_to_vendor_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_returns');

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_station_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('inspected_by');
            $table->dropColumn([
                'sent_to_warehouse_at', 'vendor_dispatch_note', 'received_at_warehouse_at', 'inspected_at',
                'inspection_result', 'inspection_notes', 'ready_for_pickup_at', 'returned_to_vendor_at',
            ]);
        });

        Schema::dropIfExists('pickup_stations');
    }
};
