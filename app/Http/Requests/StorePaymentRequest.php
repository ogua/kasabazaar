<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_payment');
    }

    public function rules(): array
    {
        return [
            'shipment_id'   => 'nullable|uuid|exists:shipments,id',
            'payment_type'  => 'nullable|in:debit,credit',
            'paying_type'   => 'nullable|string|max:100',
            'description'   => 'nullable|string',
            'amount'        => 'required|numeric|min:0',
            'currency'      => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'amount_usd'    => 'nullable|numeric|min:0',
            'amount_ghs'    => 'nullable|numeric|min:0',
            'paying_method' => 'nullable|in:cash,bank_transfer,mobile_money,cheque,card,other',
            'payment_note'  => 'nullable|string',
            'cheque_no'     => 'nullable|string',
            'bankname'      => 'nullable|string',
            'accountnumber' => 'nullable|string',
            'paid_on'       => 'nullable|date',
        ];
    }
}
