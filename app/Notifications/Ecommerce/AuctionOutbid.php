<?php

namespace App\Notifications\Ecommerce;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the previous leader when someone places a higher bid.
 */
class AuctionOutbid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Auction $auction) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You've been outbid — {$this->auction->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Someone placed a higher bid on \"{$this->auction->title}\". The current bid is GHS ".number_format((float) $this->auction->current_price_ghs, 2).'.')
            ->line('Bidding closes '.$this->auction->ends_at->format('j M Y, g:i a').'.')
            ->action('Bid again', $this->auctionUrl());
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'You have been outbid',
            'body' => "The current bid on \"{$this->auction->title}\" is GHS ".number_format((float) $this->auction->current_price_ghs, 2).'.',
            'type' => 'auction_outbid',
            'auction_id' => $this->auction->id,
            'auction_slug' => $this->auction->slug,
        ];
    }

    private function auctionUrl(): string
    {
        return rtrim((string) config('app.frontend_url'), '/')."/auctions/{$this->auction->slug}";
    }
}
