<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'shipment_id'       => $this->shipment_id,
            'status'            => $this->status,
            'description'       => $this->description,
            'location'          => $this->location,
            'status_updated_at' => $this->status_updated_at,
            'recorded_by'       => $this->recorded_by,
            'created_at'        => $this->created_at,
        ];
    }
}
