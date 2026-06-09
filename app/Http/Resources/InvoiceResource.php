<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rawStatus = $this->status instanceof \BackedEnum ? $this->status->value : $this->status;
        $status    = $rawStatus === 'pending' ? 'unpaid' : $rawStatus;

        return [
            'id'           => $this->id,
            'shipment_id'  => $this->shipment_id,
            'total_amount' => $this->total_amount,
            'status'       => $status,
            'created_at'   => $this->created_at,
            'shipment'     => $this->whenLoaded('shipment', fn () => $this->buildShipmentData()),
        ];
    }

    private function buildShipmentData(): ?array
    {
        $s = $this->shipment;
        if (!$s) {
            return null;
        }

        $data = [
            'id'                 => $s->id,
            'shipping_reference' => $s->shipping_reference,
            'status'             => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
            'payment_status'     => $s->payment_status,
            'total'              => $s->total,
            'paid'               => $s->paid,
            'shipped_at'         => $s->shipped_at,
            'destination_branch' => $s->relationLoaded('destinationBranch') ? $s->destinationBranch?->name : null,
            'client'             => $s->relationLoaded('client') && $s->client ? [
                'id'    => $s->client->id,
                'name'  => $s->client->name,
                'phone' => $s->client->phone,
                'email' => $s->client->email,
            ] : null,
        ];

        // Detail-level fields — only included when these relationships are loaded
        if ($s->relationLoaded('payments')) {
            $data['shipping_cost']           = $s->shipping_cost;
            $data['vat']                     = $s->vat ?? 0;
            $data['vat_percentage']          = $s->vat_percentage ?? 0;
            $data['insurance']               = $s->insurance ?? 0;
            $data['estimated_delivery_date'] = $s->estimated_delivery_date;
            $data['delivered_at']            = $s->delivered_at;
            $data['origin_branch']           = $s->relationLoaded('originBranch') ? $s->originBranch?->name : null;

            $data['payments'] = $s->payments->map(fn ($p) => [
                'id'            => $p->id,
                'amount'        => $p->amount,
                'paying_method' => $p->paying_method,
                'paying_type'   => $p->paying_type,
                'payment_ref'   => $p->payment_ref,
                'payment_note'  => $p->payment_note,
                'paid_on'       => $p->paid_on,
            ])->values();
        }

        if ($s->relationLoaded('items')) {
            $data['items'] = $s->items->map(fn ($item) => [
                'id'        => $item->id,
                'box_no'    => $item->box_no,
                'product'   => $item->product?->name,
                'quantity'  => $item->quantity,
                'item_cost' => $item->item_cost,
                'subtotal'  => $item->quantity * $item->item_cost,
            ])->values();
        }

        if ($s->relationLoaded('receivers')) {
            $data['receivers'] = $s->receivers->map(fn ($r) => [
                'id'   => $r->id,
                'name' => $r->receiver_name,
            ])->values();
        }

        return $data;
    }
}
