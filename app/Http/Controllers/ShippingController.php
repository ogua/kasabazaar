<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Matscode\Paystack\Transaction;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\Support\pdfpport\pdf;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Filament\Client\Pages\PageSuccessfully;

class ShippingController extends Controller
{
    public function packingslip(Shipment $id)
    {
        $shipping = $id;

        return view('packing-slip',compact('shipping'));

        return Pdf::view('packing-slip', ['shipping' => $id])
        ->format('a4')
        ->save('packing-slip.pdf');


        
        //    ->headerHtml('<div>My header</div>')
        //    ->format('a4')
        //     ->name('invoice-2023-04-10.pdf');
    }


    public function shippinginvoice(Shipment $id)
    {

        return view('shipping-invoice',['shipping' => $id]);

        return Pdf::view('shipping-invoice', ['shipping' => $id])
        ->name('shipping-invoice.pdf');
    }

    public function printquotation(Quotation $record)
    {

        dd($record);

        return Pdf::view('shipping-slip')
        ->headerHtml('<div>My header</div>')
        ->name('invoice-2023-04-10.pdf');
    }


    public function makePayment(Shipment $record)
    {


        dd($record);
        $secretKey = env('PAYSTACK_SECRET_KEY');
        // creating the transaction object
        $Transaction = new Transaction($secretKey);

        $amount = ($record->total);

        $response = (object) $Transaction
        ->setCallbackUrl(PageSuccessfully::getUrl())
        ->setEmail( $record->client?->email)
        ->setAmount($amount)
        ->setMetadata(
            [
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
