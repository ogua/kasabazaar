<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'branch_id'            => $this->branch_id,
            'trip_reference'       => $this->trip_reference,
            'origin'               => $this->origin,
            'destination'          => $this->destination,
            'route_description'    => $this->route_description,
            'distance_km'          => $this->distance_km,
            'scheduled_date'       => $this->scheduled_date,
            'scheduled_departure'  => $this->scheduled_departure,
            'scheduled_arrival'    => $this->scheduled_arrival,
            'actual_departure'     => $this->actual_departure,
            'actual_arrival'       => $this->actual_arrival,
            'status'               => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'fuel_cost'            => $this->fuel_cost,
            'toll_fees'            => $this->toll_fees,
            'driver_allowance'     => $this->driver_allowance,
            'other_costs'          => $this->other_costs,
            'total_cost'           => $this->total_cost,
            'start_mileage'        => $this->start_mileage,
            'end_mileage'          => $this->end_mileage,
            'notes'                => $this->notes,
            'created_at'           => $this->created_at,
            'vehicle'              => $this->whenLoaded('vehicle', fn () => $this->vehicle ? [
                'id'                  => $this->vehicle->id,
                'registration_number' => $this->vehicle->registration_number,
                'make'                => $this->vehicle->make,
                'model'               => $this->vehicle->model,
            ] : null),
            'driver'               => $this->whenLoaded('driver', fn () => $this->driver ? [
                'id'   => $this->driver->id,
                'name' => $this->driver->name,
            ] : null),
            'assistant'            => $this->whenLoaded('assistant', fn () => $this->assistant ? [
                'id'   => $this->assistant->id,
                'name' => $this->assistant->name,
            ] : null),
            'shipments_count'      => $this->whenCounted('shipments'),
        ];
    }
}
