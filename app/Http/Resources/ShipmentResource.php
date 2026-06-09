<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'shipping_reference'       => $this->shipping_reference,
            'tracking_number'          => $this->tracking_number,
            'status'                   => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'payment_status'           => $this->payment_status,
            'shipping_cost'            => $this->shipping_cost,
            'total'                    => $this->total,
            'paid'                     => $this->paid,
            'total_ghs'                => $this->total_ghs,
            'exchange_rate_at_shipment' => $this->exchange_rate_at_shipment,
            'container_number'         => $this->container_number,
            'vat'                      => $this->vat,
            'vat_percentage'           => $this->vat_percentage,
            'insurance'                => $this->insurance,
            'insurance_accepted'       => $this->insurance_accepted,
            'is_received'              => $this->is_received,
            'signed_received_form'     => $this->signed_received_form,
            'shipped_at'               => $this->shipped_at,
            'estimated_delivery_date'  => $this->estimated_delivery_date,
            'delivered_at'             => $this->delivered_at,
            'created_at'               => $this->created_at,
            'client'                   => $this->whenLoaded('client', fn () => [
                'id'    => $this->client->id,
                'name'  => $this->client->name,
                'phone' => $this->client->phone,
            ]),
            'origin_branch'            => $this->whenLoaded('originBranch', fn () => $this->originBranch ? [
                'id'   => $this->originBranch->id,
                'name' => $this->originBranch->name,
            ] : null),
            'destination_branch'       => $this->whenLoaded('destinationBranch', fn () => $this->destinationBranch ? [
                'id'   => $this->destinationBranch->id,
                'name' => $this->destinationBranch->name,
            ] : null),
        ];
    }
}
