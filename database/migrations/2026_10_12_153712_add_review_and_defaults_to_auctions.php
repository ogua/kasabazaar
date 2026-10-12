<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-up to the auctions launch:
 *  - vendor-submitted lots go through staff review (status pending_review,
 *    submitted_at, review_notes for a rejection reason);
 *  - unpaid wins are recorded in auction_payment_defaults, which drives the
 *    second-chance offer to the next bidder and the bidding ban for repeat
 *    defaulters. `offer_round` counts how many buyers the lot has been
 *    offered to (1 = the original winner).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dateTime('submitted_at')->nullable()->after('status');
            $table->text('review_notes')->nullable()->after('submitted_at');
            $table->unsignedTinyInteger('offer_round')->default(1)->after('payment_due_at');
        });

        Schema::create('auction_payment_defaults', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount_ghs', 12, 2);
            // winner: did not pay for a lot they won. second_chance: let a
            // second-chance offer lapse or declined it — not held against them.
            $table->string('kind');
            $table->timestamps();

            $table->index(['user_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_payment_defaults');

        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn(['submitted_at', 'review_notes', 'offer_round']);
        });
    }
};
