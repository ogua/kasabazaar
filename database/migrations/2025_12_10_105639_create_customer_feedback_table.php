<?php

use App\Models\User;
use App\Models\Shipment;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relationships
            $table->foreignIdFor(User::class, 'user_id')->nullable();
            $table->foreignIdFor(Shipment::class, 'shipment_id')->nullable();

            // Feedback source
            $table->enum('feedback_on', ['Rose Shipment', 'NeoRide Africa'])->index();

            // Customer info
            $table->string('customer_name', 255);
            $table->string('customer_email', 255);
            $table->string('customer_phone', 50);
            $table->string('invoice_number')->nullable();

            // Feedback categorization
            $table->enum('category', [
                'Delivery Speed',         // General speed of delivery
                'Item Condition',         // Condition of items delivered
                'Late Delivery',          // Delivery arrived late
                'Wrong Address',          // Delivered to the wrong address
                'Damaged Item',           // Items damaged during transport
                'Damaged Packaging',      // Packaging damaged
                'Wrong Item Sent',        // Incorrect item delivered
                'Missing Item',           // Part of the order missing
                'Driver Conduct',         // Driver behavior/customer interaction
                'Ignored Instructions',   // Instructions not followed
                'Tricycle Maintenance',   // Issues related to tricycle condition
                'Driver Safety',          // Unsafe driving or accidents
                'Route Efficiency',       // Complaints about route taken
                'Traffic Handling',       // Complaints about handling traffic
                'Other'                   // Catch-all for other feedback
            ])->default('Damaged Item');

            // Core feedback
            $table->unsignedTinyInteger('rating')
                ->comment('1 to 5 scale')
                ->check('rating >= 1 AND rating <= 5');
            $table->text('comment')->nullable();
            $table->json('attachments')->nullable();

            $table->text('response')->nullable();
            $table->string('responded_by')->nullable();
            

            // Workflow
            $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending')->index();

            // Optional meta
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_feedback');
    }
};
