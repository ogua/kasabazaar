<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'branch_id'    => $this->branch_id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'country'      => $this->country,
            'state_region' => $this->state_region,
            'city'         => $this->city,
            'address'      => $this->address,
            'id_type'      => $this->id_type,
            'id_number'    => $this->id_number,
            'created_at'   => $this->created_at,
        ];
    }
}
