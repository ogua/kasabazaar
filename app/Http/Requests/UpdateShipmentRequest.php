<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_shipment');
    }

    public function rules(): array
    {
        return [
            'status'                   => 'sometimes|string',
            'payment_status'           => 'sometimes|in:pending,paid,partial',
            'destination_branch_id'    => 'sometimes',
            'estimated_delivery_date'  => 'nullable|date',
            'shipping_cost'            => 'nullable|numeric|min:0',
            'total'                    => 'nullable|numeric|min:0',
            'paid'                     => 'nullable|numeric|min:0',
            'vat_percentage'           => 'nullable|numeric|min:0|max:1',
            'insurance_accepted'       => 'nullable|boolean',
            'insurance'                => 'nullable|numeric|min:0',
            'container_number'         => 'nullable|integer',
            'is_received'              => 'nullable|boolean',
            'delivered_at'             => 'nullable|date',
        ];
    }
}
