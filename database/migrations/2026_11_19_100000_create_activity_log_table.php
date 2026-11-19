<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The audit trail (spatie/laravel-activitylog, merged from its three published stubs).
 *
 * subject_id / causer_id / scope_id are strings: most models use uuids, but Role and
 * ShipmentContainer have integer keys and Setting a string `key`. Rows are append-only
 * (App\Models\Activity refuses updates and deletes) and are never pruned.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('event')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('subject_label')->nullable();
            $table->string('scope_type')->nullable();
            $table->string('scope_id', 64)->nullable();
            $table->string('causer_type')->nullable();
            $table->string('causer_id', 64)->nullable();
            $table->uuid('impersonator_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->string('channel', 20)->nullable();
            $table->string('source')->nullable();
            $table->string('risk', 10)->default('normal');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->text('url')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('route_name')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'activity_subject_index');
            $table->index(['causer_type', 'causer_id'], 'activity_causer_index');
            $table->index(['scope_type', 'scope_id'], 'activity_scope_index');
            $table->index(['log_name', 'created_at']);
            $table->index('event');
            $table->index('branch_id');
            $table->index('risk');
            $table->index('batch_uuid');
            $table->index('impersonator_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
};
