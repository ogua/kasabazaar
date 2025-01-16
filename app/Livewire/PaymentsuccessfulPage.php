<?php

namespace App\Livewire;

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

            $amount =  number_format(((int) $paymentdata['amount'] / 100),2);

            $ref = $paymentdata['reference'];

            $email = $paymentdata['customer']['email'];
            $phone = $paymentdata['customer']['phone'];

            $data = [
                //'vourcher_id' => $paymentdata['metadata']['form_id'],
                'transaction_id' => str_pad(mt_rand(1, 99999999999), 11, '0', STR_PAD_LEFT),
                'transaction_status' => $paymentDetails['status'],
                'reference' => $paymentdata['reference'],
                //'index_number' => $paymentdata['metadata']['form_id'],
                //'payment_type' => $paymentdata['metadata']['payment_type'],
                //'academicyear' => $paymentdata['metadata']['academicyear'],
                //'semester' => $paymentdata['metadata']['semester'],
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
                'email' => $paymentdata['customer']['email'],
                'phone' => $paymentdata['customer']['phone'],
                'log_start_time' => $paymentdata['log']['start_time'],
                'log_spent_time' => $paymentdata['log']['time_spent'],
                'log_attempts' => $paymentdata['log']['attempts'],
                'log_errors' => $paymentdata['log']['errors'],
            ];

           // dd($data);

            // $check = PaystackTransaction::where('reference',$ref)
            // ->where('vourcher_id',$form_id)
            // ->first();

            // if (!$check) {
            //     $new = new PaystackTransaction($data);
            //     $new->save();

            //     $vourcherid = $new->vourcher_id;
            //     $voucher = AdmissionVoucher::findOrFail($vourcherid);

            //     $phone = $new->phone;
            //     $email = $new->email;
            //     $url = env('APP_URL').'/admission';

            //     $message = "UniMAC Admission eVoucher ({$voucher->appform?->name}) \nApplicant SERIAL: {$voucher->serial} \nPIN: {$voucher->pin} \nTransaction ID: {$new->transaction_id} \nAmount Paid: {$amount} \nVisit {$url} to start the admission process";

            //     //send sms
            //     NotificationService::notifyApplicantBySms($phone,$message);

            //     //send email
            //     $showDate = false;
            //     Mail::to($email)->send(new SendEvoucherMail($voucher,$new,$showDate));
            // }
        }

    }

    public function render()
    {
        return view('livewire.paymentsuccessful-page');
    }
}
