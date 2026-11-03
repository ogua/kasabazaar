<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A pending app signup whose email or phone matches a client staff already
 * created. The login is only made (and linked to that client) once the code
 * sent to the client's own phone or email is entered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_signup_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('password');
            $table->boolean('whatsapp_opt_in')->default(false);
            $table->string('code_hash');
            $table->string('channel', 10);
            $table->string('destination');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('sends')->default(1);
            // dateTime, not timestamp: MySQL gives the first NOT NULL timestamp column
            // ON UPDATE CURRENT_TIMESTAMP, which would reset the expiry on every attempt.
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();

            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_signup_verifications');
    }
};
