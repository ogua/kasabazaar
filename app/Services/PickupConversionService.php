<?php

namespace App\Services;

use App\Models\PickupSchedule;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Notifications\PickupConverted;
use Illuminate\Support\Facades\DB;

class PickupConversionService
{
    public function convert(PickupSchedule $pickup, User $convertedBy): Shipment
    {
        if ($pickup->status !== 'completed') {
            throw new \InvalidArgumentException('Only completed pickups can be converted to shipments.');
        }

        if ($pickup->shipment_id) {
            throw new \InvalidArgumentException('This pickup has already been converted to a shipment.');
        }

        return DB::transaction(function () use ($pickup, $convertedBy) {
            $refData = Shipment::generateShippingReference('new');

            $shipment = Shipment::create([
                'client_id'          => $pickup->client_id,
                'branch_id'          => $pickup->branch_id,
                'status'             => 'pending',
                'shipping_reference' => $refData['reference'],
                'pickup_schedule_id' => $pickup->id,
                'client_note'        => $pickup->notes,
                'recorded_by'        => $convertedBy->id,
                'client_existence'   => 'existing',
                'origin_branch_id'   => $pickup->branch_id,
                'destination_branch_id' => $pickup->branch_id,
            ]);

            $client = $pickup->client ?? $pickup->load('client')->client;

            $receiver = Receiver::create([
                'shipment_id'    => $shipment->id,
                'receiver_name'  => $client?->name ?? 'Unknown',
                'receiver_phone' => $pickup->contact_phone ?? $client?->phone,
                'address'        => $pickup->pickup_location,
            ]);

            $pickup->load('pickupItems');
            foreach ($pickup->pickupItems as $index => $item) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'receiver_id' => $receiver->id,
                    'product_id'  => $item->product_id,
                    'quantity'    => $item->quantity,
                    'box_no'      => 'P' . ($index + 1),
                    'item_cost'   => 0.00,
                ]);
            }

            $pickup->update([
                'shipment_id'  => $shipment->id,
                'status'       => 'converted',
                'converted_at' => now(),
                'converted_by' => $convertedBy->id,
            ]);

            $shipment->load(['client:id,name,phone', 'receivers.items.product']);

            // Notify the client user
            $clientUser = User::where('client_id', $pickup->client_id)
                ->whereNotNull('client_id')
                ->first();

            if ($clientUser) {
                $clientUser->notify(new PickupConverted($pickup, $shipment));
            }

            return $shipment;
        });
    }
}
