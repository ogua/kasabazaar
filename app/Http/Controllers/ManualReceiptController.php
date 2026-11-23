<?php

namespace App\Http\Controllers;

use App\Models\ManualReceipt;
use App\Services\ManualReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * The printable and downloadable manual receipt. Both routes are signed, so the same link
 * works for staff in the admin panel and for a client opening it from an email or SMS.
 */
class ManualReceiptController extends Controller
{
    public function show(ManualReceipt $receipt, ManualReceiptService $receipts): View
    {
        return view('pdf.manual-receipt', [
            'receipt' => $receipt->load('client', 'issuer'),
            'downloadUrl' => $receipts->downloadUrl($receipt),
        ]);
    }

    public function download(ManualReceipt $receipt, ManualReceiptService $receipts): Response
    {
        return $receipts->pdf($receipt)->download($receipts->filename($receipt));
    }
}
