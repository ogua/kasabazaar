<?php

namespace App\Notifications\Ecommerce;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the winning bidder when an auction closes as sold. Mail and
 * database only: WhatsApp needs a pre-approved template, which auctions do
 * not have yet.
 */
class AuctionWon extends Notification implements ShouldQueue
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
            ->subject("You won the auction — {$this->auction->title}")
            ->greeting("Congratulations {$notifiable->name},")
            ->line("You won \"{$this->auction->title}\" with a bid of GHS ".number_format((float) $this->auction->current_price_ghs, 2).'.')
            ->line('Please complete payment by '.$this->auction->payment_due_at?->format('j M Y, g:i a').' to secure your item.')
            ->action('Pay now', $this->payUrl())
            ->line('Your winning bid is a commitment to buy. If payment is not made in time, the item may be offered to another buyer.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Auction won',
            'body' => "You won \"{$this->auction->title}\". Complete payment by {$this->auction->payment_due_at?->format('j M Y, g:i a')}.",
            'type' => 'auction_won',
            'auction_id' => $this->auction->id,
            'auction_slug' => $this->auction->slug,
            'amount_ghs' => $this->auction->current_price_ghs,
        ];
    }

    private function payUrl(): string
    {
        return rtrim((string) config('app.frontend_url'), '/')."/auctions/{$this->auction->slug}/pay";
    }
}
