<?php

namespace App\Services;

use App\Models\ManualReceipt;
use App\Service\NotificationService;
use App\Services\Audit\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Renders and delivers manual receipts. Nothing here records money: a manual receipt is
 * paperwork for a payment already received outside the system.
 */
class ManualReceiptService
{
    /**
     * Links stay valid long enough for a client to come back to an old SMS or email.
     */
    public const LINK_DAYS = 365;

    public function viewUrl(ManualReceipt $receipt): string
    {
        return URL::temporarySignedRoute('manual-receipt', now()->addDays(self::LINK_DAYS), ['receipt' => $receipt->id]);
    }

    public function downloadUrl(ManualReceipt $receipt): string
    {
        return URL::temporarySignedRoute('manual-receipt-download', now()->addDays(self::LINK_DAYS), ['receipt' => $receipt->id]);
    }

    public function pdf(ManualReceipt $receipt): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.manual-receipt', ['receipt' => $this->loaded($receipt), 'forPdf' => true])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'defaultFont' => 'sans-serif']);
    }

    public function filename(ManualReceipt $receipt): string
    {
        return 'receipt-'.$receipt->receipt_number.'.pdf';
    }

    /**
     * Email the PDF and/or text the link. Returns the channels that went out.
     *
     * @return list<string>
     */
    public function send(ManualReceipt $receipt, ?string $email, ?string $phone): array
    {
        $receipt = $this->loaded($receipt);
        $sent = [];

        if (filled($email)) {
            $pdf = $this->pdf($receipt);

            Mail::send('emails.manual-receipt', [
                'receipt' => $receipt,
                'viewUrl' => $this->viewUrl($receipt),
            ], fn ($message) => $message
                ->to($email)
                ->subject('Payment Receipt '.$receipt->receipt_number.' – '.config('group.parent.name', 'KASAROSE LOGISTICS'))
                ->attachData($pdf->output(), $this->filename($receipt), ['mime' => 'application/pdf']));

            $sent[] = 'email';
        }

        if (filled($phone)) {
            NotificationService::sendSmsToSender($phone, $this->smsText($receipt));
            $sent[] = 'sms';
        }

        if ($sent !== []) {
            $recipients = implode(', ', array_filter([$email, $phone]));

            $receipt->forceFill([
                'last_sent_at' => now(),
                'last_sent_to' => $recipients,
                'times_sent' => $receipt->times_sent + 1,
            ])->save();

            Audit::event($receipt, 'receipt_sent', "Manual receipt {$receipt->receipt_number} sent to {$recipients}", [
                'channels' => $sent,
                'recipients' => $recipients,
            ]);
        }

        return $sent;
    }

    public function smsText(ManualReceipt $receipt): string
    {
        return 'Dear '.$receipt->client?->name.', here is your receipt '.$receipt->receipt_number
            .' for '.$receipt->currency.' '.number_format((float) $receipt->amount, 2)
            .' paid on '.$receipt->paid_on->format('j M Y').'. View: '.$this->viewUrl($receipt)
            .' - '.config('group.parent.name', 'KASAROSE LOGISTICS');
    }

    private function loaded(ManualReceipt $receipt): ManualReceipt
    {
        return $receipt->loadMissing('client', 'issuer');
    }
}
