<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'alternative_phone' => $this->alternative_phone,
            'email' => $this->email,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'suburb' => $this->suburb,
            'street' => $this->street,
            'house_number' => $this->house_number,
            'digital_address' => $this->digital_address,
            'landmark' => $this->landmark,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'delivery_notes' => $this->delivery_notes,
            'is_default' => $this->is_default,
        ];
    }
}
