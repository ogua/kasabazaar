<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'recorderd_by')) {
                $table->renameColumn('recorderd_by', 'recorded_by');
            }
            if (Schema::hasColumn('shipments', 'is_diclaimer_aggred')) {
                $table->renameColumn('is_diclaimer_aggred', 'is_disclaimer_agreed');
            }
            if (Schema::hasColumn('shipments', 'is_agreement_aggred')) {
                $table->renameColumn('is_agreement_aggred', 'is_agreement_agreed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'recorded_by')) {
                $table->renameColumn('recorded_by', 'recorderd_by');
            }
            if (Schema::hasColumn('shipments', 'is_disclaimer_agreed')) {
                $table->renameColumn('is_disclaimer_agreed', 'is_diclaimer_aggred');
            }
            if (Schema::hasColumn('shipments', 'is_agreement_agreed')) {
                $table->renameColumn('is_agreement_agreed', 'is_agreement_aggred');
            }
        });
    }
};
