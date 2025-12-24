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
        Schema::create('shipment_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->nullable();
            $table->uuid('message_template_id')->nullable();
            $table->uuid('sent_by')->nullable(); // User who sent the message

            // Flexible targeting
            $table->enum('target_type', ['client', 'shipment', 'container', 'all'])->default('client');
            $table->uuid('client_id')->nullable(); // For targeting specific client
            $table->uuid('shipment_id')->nullable(); // For targeting specific shipment
            $table->integer('container_number')->nullable(); // For targeting all in a container

            $table->string('subject');
            $table->text('body');
            $table->enum('channel', ['email', 'sms', 'both'])->default('email');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('message_template_id')->references('id')->on('message_templates')->onDelete('set null');
            // Note: sent_by references users table with UUID, constraint removed for compatibility
            // $table->foreign('sent_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_messages');
    }
};
