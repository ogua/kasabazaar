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
        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->timestamp('converted_at')->nullable()->after('status');
            $table->foreignUuid('converted_by')->nullable()->constrained('users')->nullOnDelete()->after('converted_at');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_schedules', function (Blueprint $table) {
            $table->dropForeign(['converted_by']);
            $table->dropColumn(['converted_at', 'converted_by']);
        });
    }
};
