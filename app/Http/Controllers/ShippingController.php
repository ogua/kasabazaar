<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Quotation;
use Illuminate\Http\Request;
use App\Service\InvoiceService;
use Matscode\Paystack\Transaction;
use App\Http\Controllers\Controller;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Filament\Client\Pages\PageSuccessfully;

class ShippingController extends Controller
{
    /**
     * Display packing slip view
     */
    public function packingslip(Shipment $id)
    {
        $shipping = $id;
        $shipping->load(['client', 'receivers.items.product', 'receivers.mcountry', 'receivers.mstate', 'receivers.mcity']);

        return view('packing-slip', compact('shipping'));
    }

    /**
     * Display shipping label view (for Brother QL-1110NWB printer)
     * Label size: 4" x 6" (102mm x 152mm)
     */
    public function shippinglabel(Shipment $id)
    {
        $shipping = $id;
        $shipping->load(['client', 'receivers.items.product', 'receivers.mcountry', 'receivers.mstate', 'receivers.mcity']);

        return view('shipping-label', compact('shipping'));
    }

    /**
     * Display shipping invoice view (for printing)
     */
    public function shippinginvoice(Shipment $id)
    {
        $shipping = $id;
        $shipping->load(['client', 'receivers.items.product', 'pickupitems.product', 'puchaseditems', 'payments']);

        return view('pdf.shipping-invoice', ['shipping' => $shipping]);
    }

    /**
     * Download shipping invoice as PDF
     */
    public function shippinginvoicepdf(Shipment $id)
    {
        return InvoiceService::streamPdf($id);
    }

    /**
     * Download shipping invoice PDF (force download)
     */
    public function downloadinvoicepdf(Shipment $id)
    {
        return InvoiceService::downloadPdf($id);
    }

    /**
     * Display shipping receipt view
     */
    public function shippingreceipt(Shipment $id)
    {
        $shipping = $id;
        $shipping->load(['client', 'payments']);

        return view('shipping-receipt', ['shipping' => $shipping]);
    }

    /**
     * Send invoice email to client
     */
    public function sendinvoiceemail(Shipment $id)
    {
        $success = InvoiceService::sendInvoiceEmail($id);

        if ($success) {
            return back()->with('success', 'Invoice sent successfully to ' . $id->client?->email);
        }

        return back()->with('error', 'Failed to send invoice. Please check the client email address.');
    }

    /**
     * Print quotation
     */
    public function printquotation(Quotation $record)
    {
        return view('quotation', ['quotation' => $record]);
    }

    /**
     * Process payment via Paystack
     */
    public function makePayment(Shipment $record)
    {
        $secretKey = env('PAYSTACK_SECRET_KEY');
        $Transaction = new Transaction($secretKey);

        $amount = ($record->total);

        $response = (object) $Transaction
            ->setCallbackUrl(PageSuccessfully::getUrl())
            ->setEmail($record->client?->email)
            ->setAmount($amount)
            ->setMetadata([
                'fullname' => $record->client?->name,
                'phone' => $record->client?->phone,
                'email' => $record->client?->email,
                'reference' => $record->reference,
                'shipment_id' => $record->id,
            ])
            ->initialize();

        return redirect()->to($response->authorizationUrl);
    }
}
