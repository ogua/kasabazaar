<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebsiteEnquiryRequest;
use App\Models\ContactMessage;
use App\Models\CustomerFeedback;
use App\Models\Shipment;
use App\Notifications\WebsiteEnquiryReceived;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Receives the public website's contact, quote and feedback forms. Contact and quote
 * requests become Contact Messages, feedback becomes Customer Feedback — so staff work
 * them in the admin panel — and every submission is also copied to the team's inboxes.
 * Answers in plain text, which public/site/js/form.js shows to the visitor as-is.
 */
class WebsiteEnquiryController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const FORM_LABELS = [
        'quote' => 'Quote Request',
        'contact' => 'Contact Message',
        'feedback' => 'Customer Feedback',
    ];

    public function store(StoreWebsiteEnquiryRequest $request): Response
    {
        $formType = $request->validated('form_type');
        $details = $request->details();
        $sender = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'sms_opt_in' => $request->boolean('sms_opt_in'),
            'message' => $request->validated('message'),
        ];

        try {
            $formType === 'feedback'
                ? $this->storeFeedback($sender, $request)
                : $this->storeContactMessage(self::FORM_LABELS[$formType], $sender, $details);
        } catch (\Throwable $e) {
            Log::error('Website enquiry could not be saved', ['form' => $formType, 'error' => $e->getMessage()]);

            return $this->plainText('Sorry, your message could not be sent. Please try again later or contact us directly.', 500);
        }

        $recipients = config('website.form_recipients');
        if ($recipients) {
            Notification::route('mail', $recipients)
                ->notify(new WebsiteEnquiryReceived(self::FORM_LABELS[$formType], $sender, $details));
        }

        return $this->plainText('Thank you! Your message has been sent successfully.');
    }

    /**
     * @param  array{name: string, email: string, phone: string, sms_opt_in: bool, message: ?string}  $sender
     * @param  array<string, string>  $details
     */
    private function storeContactMessage(string $subject, array $sender, array $details): void
    {
        $body = collect($details)->map(fn (string $value, string $label): string => "{$label}: {$value}")->implode("\n");

        if (filled($sender['message'])) {
            $body = trim($body."\n\n".$sender['message']);
        }

        ContactMessage::create([
            'name' => $sender['name'],
            'email' => $sender['email'],
            'phone' => $sender['phone'],
            'sms_opt_in' => $sender['sms_opt_in'],
            'subject' => $subject,
            'message' => $body !== '' ? $body : '(no message)',
            'status' => 'pending',
        ]);
    }

    /**
     * @param  array{name: string, email: string, phone: string, sms_opt_in: bool, message: ?string}  $sender
     */
    private function storeFeedback(array $sender, StoreWebsiteEnquiryRequest $request): void
    {
        $reference = trim((string) $request->validated('tracking_ref'));
        $shipment = $reference === '' ? null : Shipment::query()
            ->where('tracking_number', $reference)
            ->orWhere('shipping_reference', $reference)
            ->first();

        CustomerFeedback::create([
            'customer_name' => $sender['name'],
            'customer_email' => $sender['email'],
            'customer_phone' => $sender['phone'],
            'feedback_on' => 'Rose Shipment',
            'category' => $request->validated('feedback_category') ?: 'Other',
            'invoice_number' => $shipment?->shipping_reference ?? ($reference !== '' ? $reference : null),
            'rating' => (int) $request->validated('rating'),
            'comment' => $sender['message'],
            'status' => 'pending',
            'type' => 'feedback',
            'shipment_id' => $shipment?->id,
            'meta' => array_filter([
                'source' => 'website',
                'feedback_about' => $request->validated('feedback_about'),
                'sms_opt_in' => $sender['sms_opt_in'],
            ], fn ($value): bool => $value !== null && $value !== ''),
        ]);
    }

    private function plainText(string $message, int $status = 200): Response
    {
        return response($message, $status)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
