<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Copies a public-website form submission (contact, quote or feedback) to the team's
 * inboxes in config('website.form_recipients'). The submission itself is already
 * stored as a Contact Message or Customer Feedback; this is the heads-up, with
 * reply-to set to the visitor so staff can answer straight from their inbox. It
 * replaces (not adds to) the global config('mail.reply_to') address.
 */
class WebsiteEnquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{name: string, email: string, phone: string, sms_opt_in: bool, message: ?string}  $sender
     * @param  array<string, string>  $details
     */
    public function __construct(
        public string $formLabel,
        public array $sender,
        public array $details,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("{$this->formLabel} from {$this->sender['name']}")
            ->withSymfonyMessage(fn (Email $message): Email => $message->replyTo(new Address($this->sender['email'], $this->sender['name'])))
            ->greeting($this->formLabel)
            ->line("Name: {$this->sender['name']}")
            ->line("Email: {$this->sender['email']}")
            ->line("Phone: {$this->sender['phone']}")
            ->line('SMS updates: '.($this->sender['sms_opt_in'] ? 'opted in' : 'not opted in'));

        foreach ($this->details as $label => $value) {
            $mail->line("{$label}: {$value}");
        }

        if (filled($this->sender['message'])) {
            $mail->line('Message:')->line($this->sender['message']);
        }

        return $mail->line('Sent from the contact forms on '.parse_url(config('group.parent.url'), PHP_URL_HOST).'.');
    }
}
