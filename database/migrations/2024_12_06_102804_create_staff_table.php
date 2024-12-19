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
        Schema::create('staff', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('branch_id'); // Branch where the staff works
            $table->string('name'); // Staff name
            $table->string('email')->unique(); // Staff email
            $table->string('phone'); // Staff phone number
            $table->string('position'); // Staff role (e.g., Manager, Officer)
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
