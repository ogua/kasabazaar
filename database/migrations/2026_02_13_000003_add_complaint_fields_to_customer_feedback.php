<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_feedback', function (Blueprint $table) {
            $table->string('complaint_token', 64)->nullable()->unique()->after('id');
            $table->string('type')->default('feedback')->after('feedback_on'); // feedback, complaint
            $table->string('priority')->default('normal')->after('type'); // low, normal, high, urgent
            $table->text('resolution_notes')->nullable()->after('response');
            $table->timestamp('resolved_at')->nullable()->after('resolution_notes');
        });
    }

    public function down(): void
    {
        Schema::table('customer_feedback', function (Blueprint $table) {
            $table->dropColumn(['complaint_token', 'type', 'priority', 'resolution_notes', 'resolved_at']);
        });
    }
};
