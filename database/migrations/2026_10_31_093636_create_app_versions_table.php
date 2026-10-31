<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile app store releases published from the admin panel. GET
 * /api/v1/app/update-check serves the newest active row per app and platform
 * so older builds are prompted (or, below the floor, blocked) until they update.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('app', 20)->default('client');
            $table->string('platform', 20);
            $table->string('version', 20);
            $table->unsignedInteger('version_code');
            $table->string('minimum_supported_version', 20)->nullable();
            $table->boolean('is_forced_update')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('release_notes')->nullable();
            $table->string('store_url')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['app', 'platform', 'version']);
            $table->unique(['app', 'platform', 'version_code']);
            $table->index(['app', 'platform', 'is_active', 'version_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
