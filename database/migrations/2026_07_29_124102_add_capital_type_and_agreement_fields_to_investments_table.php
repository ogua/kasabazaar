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
        Schema::table('investments', function (Blueprint $table) {
            $table->string('capital_type')->default('investment')->after('status');

            $table->string('agreement_status')->default('unsigned')->after('capital_type');
            $table->string('signed_agreement_path')->nullable()->after('agreement_status');
            $table->timestamp('agreement_signed_at')->nullable()->after('signed_agreement_path');
            $table->timestamp('agreement_finalized_at')->nullable()->after('agreement_signed_at');
            $table->uuid('agreement_finalized_by')->nullable()->after('agreement_finalized_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropColumn([
                'capital_type',
                'agreement_status',
                'signed_agreement_path',
                'agreement_signed_at',
                'agreement_finalized_at',
                'agreement_finalized_by',
            ]);
        });
    }
};
