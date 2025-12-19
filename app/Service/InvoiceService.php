<?php

namespace App\Service;

use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate PDF invoice for a shipment
     */
    public static function generatePdf(Shipment $shipment): \Barryvdh\DomPDF\PDF
    {
        $shipment->load(['client', 'receivers.items.product', 'pickupitems.product', 'payments']);

        return Pdf::loadView('pdf.shipping-invoice', [
            'shipping' => $shipment,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Download PDF invoice
     */
    public static function downloadPdf(Shipment $shipment)
    {
        $pdf = self::generatePdf($shipment);
        $filename = 'invoice-' . $shipment->shipping_reference . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Stream PDF invoice (view in browser)
     */
    public static function streamPdf(Shipment $shipment)
    {
        $pdf = self::generatePdf($shipment);
        $filename = 'invoice-' . $shipment->shipping_reference . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Save PDF invoice to storage
     */
    public static function savePdf(Shipment $shipment, string $disk = 'public'): string
    {
        $pdf = self::generatePdf($shipment);
        $filename = 'invoices/invoice-' . $shipment->shipping_reference . '-' . now()->format('Ymd') . '.pdf';

        Storage::disk($disk)->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Send invoice via email to client
     */
    public static function sendInvoiceEmail(Shipment $shipment): bool
    {
        $clientEmail = $shipment->client?->email;

        if (!$clientEmail) {
            return false;
        }

        $pdf = self::generatePdf($shipment);
        $filename = 'invoice-' . $shipment->shipping_reference . '.pdf';

        try {
            Mail::send('emails.invoice', [
                'shipment' => $shipment,
                'clientName' => $shipment->client?->name,
            ], function ($message) use ($clientEmail, $shipment, $pdf, $filename) {
                $message->to($clientEmail)
                    ->subject('Shipping Invoice - ' . $shipment->shipping_reference)
                    ->attachData($pdf->output(), $filename, [
                        'mime' => 'application/pdf',
                    ]);
            });

            // Log the email sent
            logger()->info("Invoice sent to {$clientEmail} for shipment {$shipment->shipping_reference}");

            return true;
        } catch (\Exception $e) {
            logger()->error("Failed to send invoice email: " . $e->getMessage());
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
