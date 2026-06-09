<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'receiver_name'    => $this->receiver_name,
            'receiver_phone'   => $this->receiver_phone,
            'receiver_email'   => $this->receiver_email,
            'country'          => $this->country,
            'state_region'     => $this->state_region,
            'city'             => $this->city,
            'address'          => $this->address,
            'receiver_id_type'   => $this->receiver_id_type,
            'receiver_id_number' => $this->receiver_id_number,
            'items'            => ShipmentItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
