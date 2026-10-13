<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public pickup-request links: staff send a client a unique URL and the client
 * submits a shipment request without logging in. A link may be tied to a known
 * client or left open (the client enters their own name and phone).
 *
 * clients.email is relaxed to nullable because a client submitting through an
 * open link may not have an email address.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_request_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 64)->unique();
            $table->string('label')->nullable();
            $table->foreignUuid('client_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('branch_id')->nullable()->index();
            $table->unsignedInteger('max_submissions')->nullable();
            $table->unsignedInteger('submissions_count')->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_submitted_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('shipment_requests', function (Blueprint $table) {
            $table->string('source')->default('app')->after('status');
            $table->foreignUuid('shipment_request_link_id')->nullable()->after('source')
                ->constrained()->nullOnDelete();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipment_request_link_id');
            $table->dropColumn('source');
        });

        Schema::dropIfExists('shipment_request_links');
    }
};
