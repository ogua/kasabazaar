<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_trip');
    }

    public function rules(): array
    {
        return [
            'vehicle_id'          => 'required|uuid|exists:vehicles,id',
            'driver_id'           => 'required|uuid|exists:staff,id',
            'assistant_id'        => 'nullable|uuid|exists:staff,id',
            'origin'              => 'required|string|max:255',
            'destination'         => 'required|string|max:255',
            'route_description'   => 'nullable|string',
            'distance_km'         => 'nullable|numeric|min:0',
            'scheduled_date'      => 'required|date',
            'scheduled_departure' => 'nullable|date_format:Y-m-d H:i:s',
            'scheduled_arrival'   => 'nullable|date_format:Y-m-d H:i:s',
            'fuel_cost'           => 'nullable|numeric|min:0',
            'toll_fees'           => 'nullable|numeric|min:0',
            'driver_allowance'    => 'nullable|numeric|min:0',
            'other_costs'         => 'nullable|numeric|min:0',
            'start_mileage'       => 'nullable|integer|min:0',
            'notes'               => 'nullable|string',
        ];
    }
}
