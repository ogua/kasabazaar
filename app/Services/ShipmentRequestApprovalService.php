<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentRequest;
use App\Models\User;
use App\Notifications\ShipmentRequestApproved;
use App\Service\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ShipmentRequestApprovalService
{
    public function approve(ShipmentRequest $request, User $approvedBy, array $options = []): Shipment
    {
        if ($request->shipment_id) {
            throw new \InvalidArgumentException('This request has already been approved.');
        }

        return DB::transaction(function () use ($request, $approvedBy, $options) {
            // new = open a new container, existing = join an existing shipment's container
            $shipmentType = $options['shipment_type'] ?? 'new';
            $refData = Shipment::generateShippingReference(
                $shipmentType,
                null,
                null,
                $options['existing_shipment_id'] ?? null
            );

            $clientExistence = Shipment::where('client_id', $request->client_id)->exists()
                ? 'returning-client'
                : 'new-client';

            $client = $request->client ?? $request->load('client')->client;

            // Default the cost to the sum of the requested items' estimated values
            $itemsTotal = collect($request->receivers ?? [])
                ->flatMap(fn ($r) => $r['items'] ?? [])
                ->sum(fn ($i) => (float) ($i['estimated_value'] ?? 0) * (int) ($i['quantity'] ?? 1));

            $shippingCost = (float) ($options['shipping_cost'] ?? $itemsTotal);
            $vatPercentage = (float) ($options['vat_percentage'] ?? 0);

            // Shipment must live in the approving staff's active branch so it is
            // visible in their branch-scoped views (avoids 404 after approval)
            $branchId = $options['branch_id'] ?? $client->branch_id;

            $shipment = Shipment::create([
                'client_id' => $request->client_id,
                'branch_id' => $branchId,
                'status' => 'pending',
                'shipping_reference' => $refData['reference'],
                'tracking_number' => Shipment::generateTrackingNumber(),
                'external_token' => Shipment::generateExternalToken(),
                'client_note' => $request->notes,
                'recorded_by' => $approvedBy->id,
                'client_existence' => $clientExistence,
                'origin_branch_id' => $branchId,
                'destination_branch_id' => $client->branch_id ?? $branchId,
                'shipping_cost' => $shippingCost,
                'vat_percentage' => $vatPercentage,
                'total' => round($shippingCost * (1 + $vatPercentage / 100), 2),
            ]);

            foreach ($request->receivers as $index => $receiverData) {
                $receiver = Receiver::create([
                    'shipment_id' => $shipment->id,
                    'receiver_name' => $receiverData['name'],
                    'receiver_phone' => $receiverData['phone'] ?? null,
                    'country' => $receiverData['country'] ?? null,
                    'state_region' => $receiverData['state_region'] ?? null,
                    'city' => $receiverData['city'] ?? null,
                    'address' => $receiverData['address'] ?? null,
                ]);

                foreach ($receiverData['items'] ?? [] as $itemIndex => $itemData) {
                    $product = ! empty($itemData['product_id'])
                        ? (Product::find($itemData['product_id']) ?? Product::firstOrCreate(
                            ['name' => $itemData['description']],
                            ['branch_id' => null, 'category' => 'general', 'value' => $itemData['estimated_value'] ?? 0]
                        ))
                        : Product::firstOrCreate(
                            ['name' => $itemData['description']],
                            ['branch_id' => null, 'category' => 'general', 'value' => $itemData['estimated_value'] ?? 0]
                        );

                    ShipmentItem::create([
                        'shipment_id' => $shipment->id,
                        'receiver_id' => $receiver->id,
                        'product_id' => $product->id,
                        'quantity' => $itemData['quantity'] ?? 1,
                        'box_no' => 'R'.($index + 1).'-'.($itemIndex + 1),
                        'item_cost' => $itemData['estimated_value'] ?? 0,
                    ]);
                }
            }

            $request->update([
                'shipment_id' => $shipment->id,
                'status' => 'approved',
                'reviewed_by' => $approvedBy->id,
                'reviewed_at' => now(),
            ]);

            $shipment->load(['client:id,name,phone,email', 'receivers.items.product', 'payments']);

            // Database notification to client user
            $clientUser = User::where('client_id', $request->client_id)
                ->whereNotNull('client_id')
                ->first();

            if ($clientUser) {
                try {
                    $clientUser->notify(new ShipmentRequestApproved($request, $shipment));
                } catch (\Throwable) {
                }
            }

            // The shipment's `created` event fires ShipmentObserver, which sends
            // the client the tracking + payment-link email and SMS. This block
            // additionally emails the signed invoice PDF.
            $paymentUrl = route('make-payment', $shipment->id);

            if ($client->email) {
                try {
                    $pdf = InvoiceService::generatePdf($shipment);
                    Mail::send(
                        'emails.shipment_approved',
                        ['shipment' => $shipment, 'client' => $client, 'paymentUrl' => $paymentUrl],
                        function ($m) use ($client, $shipment, $pdf) {
                            $m->to($client->email)
                                ->subject('Your Shipment Has Been Approved – '.$shipment->shipping_reference)
                                ->attachData($pdf->output(), 'invoice-'.$shipment->shipping_reference.'.pdf', [
                                    'mime' => 'application/pdf',
                                ]);
                        }
                    );
                } catch (\Throwable) {
                }
            }

            return $shipment;
        });
    }
}
