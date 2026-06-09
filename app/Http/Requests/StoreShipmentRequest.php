<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_shipment');
    }

    public function rules(): array
    {
        return [
            'client_id'                       => 'required|uuid|exists:clients,id',
            'destination_branch_id'           => 'required',
            'shipment_type'                   => 'sometimes|in:new,existing',
            'existing_shipment_id'            => 'required_if:shipment_type,existing|nullable|uuid|exists:shipments,id',
            'shipped_at'                      => 'nullable|date',
            'estimated_delivery_date'         => 'nullable|date',
            'shipping_cost'                   => 'nullable|numeric|min:0',
            'exchange_rate_at_shipment'       => 'nullable|numeric|min:0',
            'vat_percentage'                  => 'nullable|numeric|min:0|max:1',
            'insurance_accepted'              => 'nullable|boolean',
            'insurance'                       => 'nullable|numeric|min:0',
            'total'                           => 'nullable|numeric|min:0',
            'paid'                            => 'nullable|numeric|min:0',
            'payment_status'                  => 'nullable|in:pending,paid,partial',
            'receivers'                       => 'nullable|array',
            'receivers.*.receiver_name'       => 'required_with:receivers|string',
            'receivers.*.receiver_phone'      => 'nullable|string',
            'receivers.*.receiver_email'      => 'nullable|email',
            'receivers.*.country'             => 'nullable|string',
            'receivers.*.state_region'        => 'nullable|string',
            'receivers.*.city'                => 'nullable|string',
            'receivers.*.address'             => 'nullable|string',
            'receivers.*.receiver_id_type'    => 'nullable|string',
            'receivers.*.receiver_id_number'  => 'nullable|string',
            'receivers.*.items'               => 'nullable|array',
            'receivers.*.items.*.product_id'  => 'nullable|uuid|exists:products,id',
            'receivers.*.items.*.quantity'    => 'nullable|integer|min:1',
            'receivers.*.items.*.item_cost'   => 'nullable|numeric|min:0',
            'receivers.*.items.*.box_no'      => 'nullable|string',
        ];
    }
}
