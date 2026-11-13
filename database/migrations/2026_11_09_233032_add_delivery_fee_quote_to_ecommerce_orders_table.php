<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            // Delivery cost is quoted by KASAROSE's shipping team after they see the items, and the
            // customer pays it separately. not_required | awaiting_quote | quoted | paid.
            $table->string('delivery_fee_status', 20)->default('not_required')->after('shipping_weight_kg')->index();
            $table->decimal('delivery_fee_ghs', 10, 2)->nullable()->after('delivery_fee_status');
            $table->foreignUuid('delivery_fee_quoted_by')->nullable()->after('delivery_fee_ghs')->constrained('users')->nullOnDelete();
            $table->timestamp('delivery_fee_quoted_at')->nullable()->after('delivery_fee_quoted_by');
            $table->text('delivery_fee_notes')->nullable()->after('delivery_fee_quoted_at');
            $table->string('delivery_fee_payment_reference')->nullable()->unique()->after('delivery_fee_notes');
            $table->timestamp('delivery_fee_paid_at')->nullable()->after('delivery_fee_payment_reference');

            // The delivery team contacts the customer to plan how the delivery will be made.
            $table->timestamp('delivery_contacted_at')->nullable()->after('delivery_fee_paid_at');
            $table->foreignUuid('delivery_contacted_by')->nullable()->after('delivery_contacted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('delivery_scheduled_at')->nullable()->after('delivery_contacted_by');
            $table->text('delivery_plan_notes')->nullable()->after('delivery_scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_fee_quoted_by');
            $table->dropConstrainedForeignId('delivery_contacted_by');
            $table->dropColumn([
                'delivery_fee_status', 'delivery_fee_ghs', 'delivery_fee_quoted_at', 'delivery_fee_notes',
                'delivery_fee_payment_reference', 'delivery_fee_paid_at', 'delivery_contacted_at',
                'delivery_scheduled_at', 'delivery_plan_notes',
            ]);
        });
    }
};
