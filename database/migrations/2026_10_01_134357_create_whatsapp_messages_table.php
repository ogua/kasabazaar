<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per WhatsApp send handed to the Ogua gateway. The gateway reports
     * delivery later through its callback; a failure then triggers the stored
     * SMS fallback exactly once.
     */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('notification_type');
            $table->string('event', 60);
            $table->string('sender', 40);
            $table->string('phone', 32);
            $table->string('template');
            $table->string('reference')->nullable();
            $table->string('gateway_message_id')->nullable()->index();
            $table->string('status', 20)->default('pending');
            $table->string('error_code', 40)->nullable();
            $table->text('error_message')->nullable();
            $table->text('fallback_sms_body')->nullable();
            $table->string('fallback_phone', 32)->nullable();
            $table->timestamp('fallback_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
