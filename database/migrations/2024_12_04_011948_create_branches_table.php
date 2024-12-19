<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('country');
            $table->string('state');
            $table->text('address');
            $table->string('email');
            $table->string('phone');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->uuid("user_id");
            $table->uuid("branch_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('branch_user');
    }
};
