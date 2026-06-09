<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'slug'     => $this->slug,
            'country'  => $this->country,
            'state'    => $this->state,
            'address'  => $this->address,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'location' => trim(collect([$this->state, $this->country])->filter()->implode(', ')),
        ];
    }
}
