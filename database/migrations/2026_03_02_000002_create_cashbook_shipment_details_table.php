<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_shipment_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('memo_no');   // e.g. "Shipping Expenses 1"
            $table->string('shipment_no'); // e.g. "45th Shipment"
            $table->decimal('import_duty', 12, 2)->default(0);
            $table->decimal('feeding', 12, 2)->default(0);
            $table->decimal('internal_travels', 12, 2)->default(0);
            $table->decimal('loading_charges', 12, 2)->default(0);
            $table->decimal('accra_delivery', 12, 2)->default(0);
            $table->decimal('port_to_warehouse', 12, 2)->default(0);
            $table->decimal('accra_to_kumasi', 12, 2)->default(0);
            $table->decimal('kumasi_delivery', 12, 2)->default(0);
            $table->decimal('stationaries', 12, 2)->default(0);
            $table->decimal('port_entry', 12, 2)->default(0);
            $table->decimal('communication', 12, 2)->default(0);
            $table->decimal('replacement', 12, 2)->default(0);
            $table->decimal('police', 12, 2)->default(0);
            $table->decimal('gas', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0); // auto-computed
            $table->timestamps();

            $table->index('shipment_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_shipment_details');
    }
};
