<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('gateway');
            $table->string('event_type');
            $table->string('reference')->nullable();
            $table->uuid('investment_id')->nullable();
            $table->uuid('investment_withdrawal_request_id')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('gateway');
            $table->index('status');
            $table->index('investment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_webhook_events');
    }
};
