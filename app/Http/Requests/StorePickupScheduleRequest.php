<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_pickup_schedule');
    }

    public function rules(): array
    {
        return [
            'client_id'         => 'required|uuid|exists:clients,id',
            'shipment_id'       => 'nullable|uuid|exists:shipments,id',
            'assigned_to'       => 'nullable|uuid|exists:staff,id',
            'scheduled_at'      => 'required|date',
            'pickup_location'   => 'required|string|max:255',
            'contact_phone'     => 'nullable|string|max:30',
            'notes'             => 'nullable|string',
            'items_description' => 'nullable|string',
        ];
    }
}
