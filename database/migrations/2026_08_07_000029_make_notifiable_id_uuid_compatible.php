<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The stock `notifications` table (Laravel\Notifications) assumes
 * auto-increment integer primary keys via $table->morphs('notifiable'),
 * but every notifiable model in this app (User, etc.) uses UUID primary
 * keys. Under strict SQL mode this fails outright rather than silently
 * truncating — any ->notify() call to a database-channel notification
 * was broken. notifiable_id needs to be UUID-compatible like the rest
 * of the app's polymorphic/foreign key columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('notifiable_id')->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_type_notifiable_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('notifiable_id')->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_type_notifiable_id_index');
        });
    }
};
