<?php

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
        Schema::create('blogs', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('cat_id');
            $table->string('img')->nullable();
            $table->string('slug');
            $table->string('title');
            $table->text('description');
            $table->text('keywords')->nullable();
            $table->text('content');
            $table->string('views')->default(0);
            $table->string('references')->nullable();
            $table->string('postedby')->nullable();
            $table->datetime('published_at')->nullable();
            $table->string('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
