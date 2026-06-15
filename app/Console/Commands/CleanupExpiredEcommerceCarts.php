<?php

namespace App\Console\Commands;

use App\Models\EcommerceCart;
use Illuminate\Console\Command;

class CleanupExpiredEcommerceCarts extends Command
{
    protected $signature = 'app:cleanup-expired-ecommerce-carts';

    protected $description = 'Delete ecommerce carts that have passed their expiry date.';

    public function handle(): int
    {
        $deleted = EcommerceCart::where('expires_at', '<', now())->get();

        $count = 0;

        foreach ($deleted as $cart) {
            $cart->items()->delete();
            $cart->delete();
            $count++;
        }

        $this->info("Deleted {$count} expired ecommerce cart(s).");

        return self::SUCCESS;
    }
}
