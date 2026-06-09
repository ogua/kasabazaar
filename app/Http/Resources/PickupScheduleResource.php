<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'status'           => $this->status,
            'scheduled_at'     => $this->scheduled_at,
            'pickup_location'  => $this->pickup_location,
            'contact_phone'    => $this->contact_phone,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at,
            'client'           => $this->whenLoaded('client', fn () => $this->client ? [
                'id'    => $this->client->id,
                'name'  => $this->client->name,
                'phone' => $this->client->phone,
            ] : null),
            'assigned_staff'   => $this->whenLoaded('assignedStaff', fn () => $this->assignedStaff ? [
                'id'   => $this->assignedStaff->id,
                'name' => $this->assignedStaff->name,
            ] : null),
        ];
    }
}
