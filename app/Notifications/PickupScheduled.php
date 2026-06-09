<?php

namespace App\Notifications;

use App\Models\PickupSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PickupScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PickupSchedule $pickup) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $date = $this->pickup->scheduled_at?->format('D, d M Y') ?? 'TBD';

        return [
            'title'     => 'Pickup Confirmed',
            'body'      => "Your pickup has been confirmed for {$date} at {$this->pickup->pickup_location}.",
            'type'      => 'pickup_scheduled',
            'pickup_id' => $this->pickup->id,
            'scheduled_at' => $this->pickup->scheduled_at,
        ];
    }

    public function toFcm(): array
    {
        $date = $this->pickup->scheduled_at?->format('D, d M Y') ?? 'TBD';

        return [
            'title' => 'Pickup Confirmed',
            'body'  => "Your pickup has been confirmed for {$date}.",
            'data'  => [
                'type'      => 'pickup_scheduled',
                'pickup_id' => $this->pickup->id,
            ],
        ];
    }
}
