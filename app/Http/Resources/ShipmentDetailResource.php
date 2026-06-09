<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = (new ShipmentResource($this->resource))->toArray($request);

        return array_merge($base, [
            'receivers'      => ReceiverResource::collection($this->whenLoaded('receivers')),
            'media'          => $this->whenLoaded('media', fn () =>
                $this->media->map(fn ($m) => [
                    'id'         => $m->id,
                    'media_type' => $m->type,
                    'file_path'  => $m->file_path,
                    'stage'      => $m->stage,
                    'caption'    => $m->caption,
                ])
            ),
            'invoice'        => $this->whenLoaded('invoice', fn () => $this->invoice ? [
                'id'           => $this->invoice->id,
                'total_amount' => $this->invoice->total_amount,
                'status'       => $this->invoice->status,
            ] : null),
            'payments_total' => $this->whenLoaded('payments', fn () => $this->payments->sum('amount_usd')),
        ]);
    }
}
