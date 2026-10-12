<?php

namespace App\Notifications\Ecommerce;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Offered to the next-highest bidder when the winner did not pay. Accepting
 * is optional — they can pay or decline from the auction page.
 */
class AuctionSecondChance extends Notification implements ShouldQueue
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
            ->subject("Second chance: {$this->auction->title} is yours if you want it")
            ->greeting("Hi {$notifiable->name},")
            ->line("The winning bidder did not complete payment for \"{$this->auction->title}\". You can buy it at your highest bid of GHS ".number_format((float) $this->auction->current_price_ghs, 2).'.')
            ->line('This offer is optional. It is open until '.$this->auction->payment_due_at?->format('j M Y, g:i a').'.')
            ->action('Review the offer', $this->auctionUrl())
            ->line('Not interested? You can decline from the auction page and nothing is charged.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Second-chance offer',
            'body' => "You can buy \"{$this->auction->title}\" at GHS ".number_format((float) $this->auction->current_price_ghs, 2).'.',
            'type' => 'auction_second_chance',
            'auction_id' => $this->auction->id,
            'auction_slug' => $this->auction->slug,
        ];
    }

    private function auctionUrl(): string
    {
        return rtrim((string) config('app.frontend_url'), '/')."/auctions/{$this->auction->slug}";
    }
}
