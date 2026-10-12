<?php

namespace App\Console\Commands;

use App\Services\AuctionService;
use Illuminate\Console\Command;

class CloseEndedAuctions extends Command
{
    protected $signature = 'app:close-ended-auctions';

    protected $description = 'Settle published auctions whose end time has passed: record the outcome and notify the winner.';

    public function handle(AuctionService $auctionService): int
    {
        $count = $auctionService->closeDue();

        $this->info("Closed {$count} auction(s).");

        return self::SUCCESS;
    }
}
