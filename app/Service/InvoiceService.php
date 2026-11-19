<?php

namespace App\Service;

use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate PDF invoice for a shipment
     */
    public static function generatePdf(Shipment $shipment): \Barryvdh\DomPDF\PDF
    {
        $shipment->load(self::INVOICE_RELATIONS);

        $pdf = Pdf::loadView('pdf.shipping-invoice', [
            'shipping' => $shipment,
            'forPdf' => true,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $pdf->render();
        self::stampPageNumbers($pdf->getDomPDF());

        return $pdf;
    }

    /**
     * Relations the invoice template reads.
     *
     * @var list<string>
     */
    public const INVOICE_RELATIONS = [
        'client',
        'receivers.items.product',
        'receivers.mcountry',
        'receivers.mstate',
        'receivers.mcity',
        'payments',
    ];

    /**
     * Print "page / pages" in the bottom-right corner of every page. dompdf's CSS has no
     * total-pages counter, so this is drawn on the canvas once the page count is known.
     */
    private static function stampPageNumbers(Dompdf $dompdf): void
    {
        $dompdf->getCanvas()->page_script(function (int $pageNumber, int $pageCount, Canvas $canvas, FontMetrics $fontMetrics): void {
            $text = "{$pageNumber} / {$pageCount}";
            $font = $fontMetrics->getFont('Helvetica');
            $size = 8;
            $width = $fontMetrics->getTextWidth($text, $font, $size);

            $canvas->text($canvas->get_width() - 36 - $width, $canvas->get_height() - 24, $text, $font, $size, [0.42, 0.45, 0.5]);
        });
    }

    /**
     * Download PDF invoice
     */
    public static function downloadPdf(Shipment $shipment)
    {
        $pdf = self::generatePdf($shipment);
        $filename = 'invoice-'.$shipment->shipping_reference.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Stream PDF invoice (view in browser)
     */
    public static function streamPdf(Shipment $shipment)
    {
        $pdf = self::generatePdf($shipment);
        $filename = 'invoice-'.$shipment->shipping_reference.'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Save PDF invoice to storage
     */
    public static function savePdf(Shipment $shipment, string $disk = 'public'): string
    {
        $pdf = self::generatePdf($shipment);
        $filename = 'invoices/invoice-'.$shipment->shipping_reference.'-'.now()->format('Ymd').'.pdf';

        Storage::disk($disk)->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Send invoice via email to client
     */
    public static function sendInvoiceEmail(Shipment $shipment): bool
    {
        $clientEmail = $shipment->client?->email;

        if (! $clientEmail) {
            return false;
        }

        $pdf = self::generatePdf($shipment);
        $filename = 'invoice-'.$shipment->shipping_reference.'.pdf';

        try {
            Mail::send('emails.invoice', [
                'shipment' => $shipment,
                'clientName' => $shipment->client?->name,
                'paymentUrl' => route('make-payment', $shipment->id),
            ], function ($message) use ($clientEmail, $shipment, $pdf, $filename) {
                $message->to($clientEmail)
                    ->subject('Shipping Invoice - '.$shipment->shipping_reference)
                    ->attachData($pdf->output(), $filename, [
                        'mime' => 'application/pdf',
                    ]);
            });

            // Log the email sent
            logger()->info("Invoice sent to {$clientEmail} for shipment {$shipment->shipping_reference}");
            \App\Services\Audit\Audit::event($shipment, 'invoice_emailed', "Invoice for shipment {$shipment->shipping_reference} emailed to {$clientEmail}", [
                'recipient' => $clientEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to send invoice email: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Generate invoice data array for templates
     */
    public static function getInvoiceData(Shipment $shipment): array
    {
        $shipment->load(['client', 'receivers.items.product', 'pickupitems.product', 'payments']);

        $pickupTotal = $shipment->pickupitems->sum(function ($item) {
            return $item->quantity * $item->item_cost;
        });

        $shippingTotal = $shipment->receivers->sum(function ($receiver) {
            return $receiver->items->sum('item_cost');
        });

        $totalQuantity = $shipment->receivers->sum(function ($receiver) {
            return $receiver->items->sum('quantity');
        });

        $amountPaid = $shipment->payments->sum('amount');
        $balance = $shipment->total - $amountPaid;

        return [
            'shipment' => $shipment,
            'client' => $shipment->client,
            'receivers' => $shipment->receivers,
            'pickupItems' => $shipment->pickupitems,
            'payments' => $shipment->payments,
            'pickupTotal' => $pickupTotal,
            'shippingTotal' => $shippingTotal,
            'totalQuantity' => $totalQuantity,
            'grandTotal' => $shipment->total,
            'amountPaid' => $amountPaid,
            'balance' => max(0, $balance),
            'invoiceDate' => $shipment->created_at->format('j M Y'),
            'reference' => $shipment->shipping_reference,
            'trackingNumber' => $shipment->tracking_number,
        ];
    }
}
