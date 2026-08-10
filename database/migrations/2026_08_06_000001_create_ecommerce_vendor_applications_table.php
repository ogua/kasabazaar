<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_vendor_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('business_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('product_category')->nullable();
            $table->text('message')->nullable();
            $table->string('business_certificate_path');
            $table->string('ghana_card_front_path');
            $table->string('ghana_card_back_path');
            $table->string('proof_of_address_path')->nullable();
            $table->string('status')->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_vendor_applications');
    }
};
