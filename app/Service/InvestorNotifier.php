<?php

namespace App\Service;

use App\Models\User;
use Illuminate\Notifications\Notification;

class InvestorNotifier
{
    /**
     * Notify every login user tied to an investor, tolerating a missing/failed notify
     * so a notification problem never breaks the caller's transaction.
     */
    public static function notify(string $investorId, Notification $notification): void
    {
        $users = User::where('investor_id', $investorId)->whereNotNull('investor_id')->get();

        foreach ($users as $user) {
            try {
                $user->notify($notification);
            } catch (\Throwable) {
            }
        }
    }
}
