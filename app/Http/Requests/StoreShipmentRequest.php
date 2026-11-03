<?php

namespace App\Http\Requests;

use App\Enums\ShippingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_shipment');
    }

    /**
     * Mirrors the Filament shipment wizard. Money follows the wizard: shipping_cost
     * is the subtotal (the sum of the item lines when left out), vat_percentage is
     * 0-100, and the total is worked out on the server.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|uuid|exists:clients,id',
            'client_existence' => 'nullable|in:new-client,returning-client',
            'origin_branch_id' => 'nullable|uuid|exists:branches,id',
            'destination_branch_id' => 'required',
            'shipment_type' => 'sometimes|in:new,existing',
            'existing_shipment_id' => 'required_if:shipment_type,existing|nullable|uuid|exists:shipments,id',
            'status' => ['nullable', 'string', Rule::enum(ShippingStatus::class)],
            'shipped_at' => 'nullable|date',
            'estimated_delivery_date' => 'nullable|date',
            'shipping_cost' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'exchange_rate_at_shipment' => 'nullable|numeric|min:0',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'insurance_accepted' => 'nullable|boolean',
            'insurance' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'client_note' => 'nullable|string|max:2000',
            'receivers' => 'nullable|array',
            'receivers.*.receiver_name' => 'required_with:receivers|string',
            'receivers.*.receiver_phone' => 'nullable|string',
            'receivers.*.receiver_email' => 'nullable|email',
            'receivers.*.country_id' => 'nullable|integer|exists:countries,id',
            'receivers.*.state_id' => 'nullable|integer|exists:states,id',
            'receivers.*.city_id' => 'nullable|integer|exists:cities,id',
            'receivers.*.country' => 'nullable|string',
            'receivers.*.state_region' => 'nullable|string',
            'receivers.*.city' => 'nullable|string',
            'receivers.*.address' => 'nullable|string',
            'receivers.*.receiver_id_type' => 'nullable|string',
            'receivers.*.receiver_id_number' => 'nullable|string',
            'receivers.*.whatsapp_opt_in' => 'nullable|boolean',
            'receivers.*.items' => 'nullable|array',
            'receivers.*.items.*.product_id' => 'nullable|uuid|exists:products,id',
            'receivers.*.items.*.quantity' => 'nullable|integer|min:1',
            'receivers.*.items.*.item_cost' => 'nullable|numeric|min:0',
            'receivers.*.items.*.box_no' => 'nullable|string',
            'receivers.*.items.*.is_vehicle' => 'nullable|boolean',
            'receivers.*.items.*.vin' => 'nullable|string|max:50',
            'receivers.*.items.*.vehicle_make' => 'nullable|string|max:100',
            'receivers.*.items.*.vehicle_model' => 'nullable|string|max:100',
            'receivers.*.items.*.vehicle_year' => 'nullable|integer|min:1900|max:2100',
            'payments' => 'nullable|array',
            'payments.*.paying_method' => 'required_with:payments|string|max:50',
            'payments.*.amount_usd' => 'required_with:payments|numeric|min:0.01',
            'payments.*.exchange_rate' => 'nullable|numeric|min:0',
            'payments.*.amount_ghs' => 'nullable|numeric|min:0',
            'payments.*.paid_on' => 'nullable|date',
            'payments.*.payment_note' => 'nullable|string|max:1000',
            'payments.*.bankname' => 'nullable|string|max:255',
            'payments.*.accountnumber' => 'nullable|string|max:255',
            'payments.*.cheque_no' => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vat_percentage.max' => 'VAT is a percentage between 0 and 100.',
            'payments.*.amount_usd.required_with' => 'Enter the amount of every payment.',
        ];
    }
}
