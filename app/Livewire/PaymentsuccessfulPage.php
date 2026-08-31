<?php

namespace App\Livewire;

use App\Models\Transaction;
use Livewire\Component;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentsuccessfulPage extends Component
{
    public $hasPaid = false;

    public function mount()
    {
        $this->hasPaid = false;

        if (request()->has(['trxref', 'reference'])) {

            $paymentDetails = Paystack::getPaymentData();

            // dd($paymentDetails);

            $paymentdata = $paymentDetails['data'];

            $amount = number_format(((int) $paymentdata['amount'] / 100), 2);

            $ref = $paymentdata['reference'];

            $email = $paymentdata['customer']['email'];
            $phone = $paymentdata['customer']['phone'];
            $shippingid = $paymentdata['metadata']['shipment_id'];

            $data = [
                'transaction_id' => str_pad(mt_rand(1, 99999999999), 11, '0', STR_PAD_LEFT),
                'transaction_status' => $paymentDetails['status'],
                'reference' => $paymentdata['reference'],

                'client_fullname' => $paymentdata['metadata']['fullname'],
                'phone' => $paymentdata['metadata']['phone'],
                'email' => $paymentdata['metadata']['email'],
                'shipment_reference' => $paymentdata['metadata']['reference'],
                'shipment_id' => $paymentdata['metadata']['shipment_id'],

                'amount' => $amount,
                'message' => $paymentDetails['message'],
                'reponse' => $paymentdata['gateway_response'],
                'payment_date' => $paymentdata['paid_at'],
                'channel' => $paymentdata['channel'],
                'currency' => $paymentdata['currency'],
                'ipaddress' => $paymentdata['ip_address'],
                'fee_charge' => 10,
                'authcode' => $paymentdata['authorization']['authorization_code'],
                'card_type' => $paymentdata['authorization']['card_type'],
                'bank' => $paymentdata['authorization']['bank'],
                'countrycode' => $paymentdata['authorization']['country_code'],
                'brand' => $paymentdata['authorization']['brand'],
                'mobile_money_number' => $paymentdata['authorization']['mobile_money_number'],
                'full_name' => $paymentdata['customer']['last_name'].' '.$paymentdata['customer']['first_name'],
                'code' => $paymentdata['customer']['customer_code'],
                // 'email' => $paymentdata['customer']['email'],
                // 'phone' => $paymentdata['customer']['phone'],
                'log_start_time' => $paymentdata['log']['start_time'],
                'log_spent_time' => $paymentdata['log']['time_spent'],
                'log_attempts' => $paymentdata['log']['attempts'],
                'log_errors' => $paymentdata['log']['errors'],
            ];

            $check = Transaction::where('reference', $ref)
                ->where('shipment_id', $shippingid)
                ->first();

            if (! $check) {
                $new = new Transaction($data);
                $new->save();
            }

            // Verify + record the payment (idempotent on the reference).
            // PaymentObserver fires the email + SMS confirmation.
            app(\App\Services\ShipmentPaymentService::class)->verify($ref);
        }

    }

    public function render()
    {
        return view('livewire.paymentsuccessful-page');
    }
}
