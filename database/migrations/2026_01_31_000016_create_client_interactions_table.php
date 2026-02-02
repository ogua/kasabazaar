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
        Schema::create('client_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->uuid('staff_id');

            $table->enum('interaction_type', ['call', 'email', 'meeting', 'whatsapp', 'visit', 'complaint', 'inquiry']);
            $table->string('subject');
            $table->text('notes');
            $table->enum('outcome', ['positive', 'neutral', 'negative', 'follow_up_needed'])->nullable();
            $table->dateTime('follow_up_date')->nullable();
            $table->boolean('follow_up_completed')->default(false);

            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_interactions');
    }
};
