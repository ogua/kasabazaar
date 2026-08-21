<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->uuid('converted_from_conversion_id')->nullable()->after('notes');
            $table->index('converted_from_conversion_id');
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropIndex(['converted_from_conversion_id']);
            $table->dropColumn('converted_from_conversion_id');
        });
    }
};
