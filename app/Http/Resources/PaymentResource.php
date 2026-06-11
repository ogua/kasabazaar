<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'branch_id'      => $this->branch_id,
            'shipment_id'    => $this->shipment_id,
            'payment_ref'    => $this->payment_ref,
            'payment_type'   => $this->payment_type,
            'paying_type'    => $this->paying_type,
            'description'    => $this->description,
            'amount'         => $this->amount,
            'balance'        => $this->balance,
            'change'         => $this->change,
            'currency'       => $this->currency,
            'exchange_rate'  => $this->exchange_rate,
            'amount_usd'     => $this->amount_usd,
            'amount_ghs'     => $this->amount_ghs,
            'paying_method'  => $this->paying_method,
            'payment_note'   => $this->payment_note,
            'cheque_no'      => $this->cheque_no,
            'bankname'       => $this->bankname,
            'accountnumber'  => $this->accountnumber,
            'paid_on'        => $this->paid_on,
            'created_at'     => $this->created_at,
            'shipment'       => $this->whenLoaded('shipment', fn () => $this->shipment ? [
                'id'                 => $this->shipment->id,
                'shipping_reference' => $this->shipment->shipping_reference,
            ] : null),
            'entered_by'     => $this->whenLoaded('enteredBy', fn () => $this->enteredBy ? [
                'id'   => $this->enteredBy->id,
                'name' => $this->enteredBy->name,
            ] : null),
            'invoice_url'    => $this->shipment_id ? url("/shipping-invoice-download/{$this->shipment_id}") : null,
            'receipt_url'    => $this->shipment_id ? url("/shipping-receipt/{$this->shipment_id}") : null,
        ];
    }
}
