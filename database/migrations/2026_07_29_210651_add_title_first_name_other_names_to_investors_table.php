<?php

use App\Models\Investor;
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
        Schema::table('investors', function (Blueprint $table) {
            $table->string('title')->nullable()->after('id');
            $table->string('first_name')->nullable()->after('title');
            $table->string('other_names')->nullable()->after('first_name');
        });

        Investor::query()->whereNull('first_name')->get()->each(function (Investor $investor) {
            $parts = preg_split('/\s+/', trim((string) $investor->name));
            $investor->forceFill([
                'first_name' => $parts[0] ?? $investor->name,
                'other_names' => count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null,
            ])->saveQuietly();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->dropColumn(['title', 'first_name', 'other_names']);
        });
    }
};
