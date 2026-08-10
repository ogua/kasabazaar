<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `clients` table was originally designed for logistics shipment receivers
 * (id_type/id_number/address/phone all required there). The ecommerce customer
 * registration flow (CustomerAuthController::register()) reuses this same table
 * for marketplace customer accounts, but its own validation already treats
 * phone/country/city/address as optional and has no concept of id_type/id_number
 * at all — so any signup that left those blank hit a NOT NULL constraint
 * violation. Relaxing them to nullable fixes marketplace registration without
 * touching the logistics receiver-creation flows, whose own request validation
 * (not this DB constraint) is what actually enforces those fields being required
 * there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->string('id_type')->nullable()->change();
            $table->string('id_number')->nullable()->change();
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->string('id_type')->nullable(false)->change();
            $table->string('id_number')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });
    }
};
