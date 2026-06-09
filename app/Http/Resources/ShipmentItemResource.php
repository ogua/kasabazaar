<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'box_no'     => $this->box_no,
            'quantity'   => $this->quantity,
            'item_cost'  => $this->item_cost,
            'weight'     => $this->weight,
            'product'    => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
            ]),
        ];
    }
}
