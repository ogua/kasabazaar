<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            // Snapshotted at the moment the agreement is generated, never read live
            // from the investor. Investor::booted() recomposes `name` from
            // title/first_name/other_names on every save, so reading it live would
            // let a later rename retroactively alter an already-issued document.
            $table->string('agreement_signature_name')->nullable()->after('agreement_finalized_by');
            $table->timestamp('agreement_signature_affixed_at')->nullable()->after('agreement_signature_name');

            // Captured when the investor returns the document, as evidence of assent.
            $table->string('agreement_acknowledged_ip', 45)->nullable()->after('agreement_signature_affixed_at');
        });
    }

    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn([
                'agreement_signature_name',
                'agreement_signature_affixed_at',
                'agreement_acknowledged_ip',
            ]);
        });
    }
};
