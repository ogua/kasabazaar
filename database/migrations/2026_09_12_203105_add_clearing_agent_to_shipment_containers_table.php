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
        Schema::table('shipment_containers', function (Blueprint $table) {
            $table->foreignUuid('clearing_agent_id')->nullable()->after('is_cleared')->constrained('clearing_agents')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable()->after('clearing_agent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_containers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clearing_agent_id');
            $table->dropColumn('cleared_at');
        });
    }
};
