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
use App\Service\NotificationService;
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
            $refData = Shipment::generateShippingReference('new');

            $clientExistence = Shipment::where('client_id', $request->client_id)->exists()
                ? 'returning-client'
                : 'new-client';

            $client = $request->client ?? $request->load('client')->client;

            $shipment = Shipment::create([
                'client_id'             => $request->client_id,
                'branch_id'             => $client->branch_id,
                'status'                => 'pending',
                'shipping_reference'    => $refData['reference'],
                'client_note'           => $request->notes,
                'recorded_by'           => $approvedBy->id,
                'client_existence'      => $clientExistence,
                'origin_branch_id'      => $client->branch_id,
                'destination_branch_id' => $client->branch_id,
                'shipping_cost'         => $options['shipping_cost'] ?? null,
                'vat_percentage'        => $options['vat_percentage'] ?? null,
            ]);

            foreach ($request->receivers as $index => $receiverData) {
                $receiver = Receiver::create([
                    'shipment_id'    => $shipment->id,
                    'receiver_name'  => $receiverData['name'],
                    'receiver_phone' => $receiverData['phone'] ?? null,
                    'country'        => $receiverData['country'] ?? null,
                    'state_region'   => $receiverData['state_region'] ?? null,
                    'city'           => $receiverData['city'] ?? null,
                    'address'        => $receiverData['address'] ?? null,
                ]);

                foreach ($receiverData['items'] ?? [] as $itemIndex => $itemData) {
                    $product = !empty($itemData['product_id'])
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
                        'product_id'  => $product->id,
                        'quantity'    => $itemData['quantity'] ?? 1,
                        'box_no'      => 'R' . ($index + 1) . '-' . ($itemIndex + 1),
                        'item_cost'   => $itemData['estimated_value'] ?? 0,
                    ]);
                }
            }

            $request->update([
                'shipment_id' => $shipment->id,
                'status'      => 'approved',
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
                } catch (\Throwable) {}
            }

            // Email + SMS to client
            $paymentUrl = route('make-payment', $shipment->id);

            if ($client->email) {
                try {
                    $pdf = InvoiceService::generatePdf($shipment);
                    Mail::send(
                        'emails.shipment_approved',
                        ['shipment' => $shipment, 'client' => $client, 'paymentUrl' => $paymentUrl],
                        function ($m) use ($client, $shipment, $pdf) {
                            $m->to($client->email)
                                ->subject('Your Shipment Has Been Approved – ' . $shipment->shipping_reference)
                                ->attachData($pdf->output(), 'invoice-' . $shipment->shipping_reference . '.pdf', [
                                    'mime' => 'application/pdf',
                                ]);
                        }
                    );
                } catch (\Throwable) {}
            }

            if ($client->phone) {
                NotificationService::sendSmsToSender(
                    $client->phone,
                    "Hi {$client->name}, your shipment request has been approved! " .
                    "Ref: {$shipment->shipping_reference}. Pay here: {$paymentUrl}"
                );
            }

            return $shipment;
        });
    }
}
