<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WhatsApp needs the recipient's opt-in before business-initiated messages.
     * Clients (logistics senders), receivers (attested by the client) and users
     * (marketplace customers and vendors) each record their own consent; a STOP
     * reply sets whatsapp_opted_out_at, which always wins over an opt-in.
     */
    public function up(): void
    {
        foreach (['clients', 'receivers', 'users'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('whatsapp_opt_in_at')->nullable();
                $table->timestamp('whatsapp_opted_out_at')->nullable();
            });
        }

        Schema::table('receivers', function (Blueprint $table) {
            $table->string('whatsapp_opt_in_source', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('receivers', function (Blueprint $table) {
            $table->dropColumn('whatsapp_opt_in_source');
        });

        foreach (['clients', 'receivers', 'users'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['whatsapp_opt_in_at', 'whatsapp_opted_out_at']);
            });
        }
    }
};
