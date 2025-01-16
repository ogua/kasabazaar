<?php

namespace App\Http\Controllers;

use App\Filament\Client\Pages\PageSuccessfully;
use App\Models\Quotation;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use function Spatie\LaravelPdf\Support\pdf;
use Matscode\Paystack\Transaction;
use Unicodeveloper\Paystack\Facades\Paystack;

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
