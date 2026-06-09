<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = (new TripResource($this->resource))->toArray($request);

        return array_merge($base, [
            'shipments' => $this->whenLoaded('shipments', fn () =>
                $this->shipments->map(fn ($s) => [
                    'id'                 => $s->id,
                    'shipping_reference' => $s->shipping_reference,
                    'tracking_number'    => $s->tracking_number,
                    'status'             => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
                    'client'             => $s->client ? ['id' => $s->client->id, 'name' => $s->client->name] : null,
                    'delivery_status'    => $s->pivot->delivery_status instanceof \BackedEnum
                        ? $s->pivot->delivery_status->value
                        : $s->pivot->delivery_status,
                    'delivery_notes'     => $s->pivot->delivery_notes,
                    'delivered_at'       => $s->pivot->delivered_at,
                ])
            ),
        ]);
    }
}
