<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_containers', function (Blueprint $table) {
            $table->id();
            $table->integer('container_number')->unique();
            $table->string('container_year', 2)->nullable(); // 2-digit year e.g. "25"
            $table->boolean('is_cleared')->default(false);
            $table->text('review')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_containers');
    }
};
