<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentRequest;
use App\Models\User;
use App\Notifications\ShipmentRequestApproved;
use App\Notifications\ShipmentRequestRejected;
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

        $unpriced = collect($request->receivers ?? [])
            ->flatMap(fn ($receiver) => $receiver['items'] ?? [])
            ->filter(fn ($item) => blank($item['product_id'] ?? null) || ! Product::whereKey($item['product_id'])->exists())
            ->pluck('description')
            ->filter();

        if ($unpriced->isNotEmpty()) {
            throw new \InvalidArgumentException(
                'Pick or create a catalogue product for: '.$unpriced->implode(', ').'. Edit the request first, or use Convert to Shipment.'
            );
        }

        return DB::transaction(function () use ($request, $approvedBy, $options) {
            // new = open a new container, existing = join an existing shipment's container
            $shipmentType = $options['shipment_type'] ?? 'new';
            $referenceFields = ShipmentCreationService::referenceFields($shipmentType, now(), $options['existing_shipment_id'] ?? null);

            $clientExistence = Shipment::where('client_id', $request->client_id)->exists()
                ? 'returning-client'
                : 'new-client';

            $client = $request->client ?? $request->load('client')->client;

            // Default the cost to the sum of the requested items' estimated values
            $shippingCost = (float) ($options['shipping_cost'] ?? $request->itemsTotal());
            $vatPercentage = min(100, max(0, (float) ($options['vat_percentage'] ?? 0)));
            $vat = round($shippingCost * $vatPercentage / 100, 2);
            $insuranceAccepted = (bool) ($options['insurance_accepted'] ?? false);
            $insurance = $insuranceAccepted ? (float) ($options['insurance'] ?? 0) : 0.0;

            // Shipment must live in the approving staff's active branch so it is
            // visible in their branch-scoped views (avoids 404 after approval)
            $branchId = $options['branch_id'] ?? $client->branch_id;

            $shipment = Shipment::create([
                'client_id' => $request->client_id,
                'branch_id' => $branchId,
                'status' => 'pending',
                ...$referenceFields,
                'tracking_number' => Shipment::generateTrackingNumber(),
                'external_token' => Shipment::generateExternalToken(),
                'client_note' => $request->notes,
                'description' => filled($options['notes'] ?? null) && $options['notes'] !== $request->notes ? $options['notes'] : null,
                'recorded_by' => $approvedBy->id,
                'client_existence' => $clientExistence,
                'origin_branch_id' => $branchId,
                'destination_branch_id' => $client->branch_id ?? $branchId,
                'shipping_cost' => $shippingCost,
                'vat_percentage' => $vatPercentage,
                'vat' => $vat,
                'insurance_accepted' => $insuranceAccepted,
                'insurance' => $insurance,
                'total' => round($shippingCost + $vat + $insurance, 2),
            ]);

            $products = Product::whereIn('id', collect($request->receivers)->flatMap(fn ($r) => $r['items'] ?? [])->pluck('product_id'))
                ->get(['id', 'value'])
                ->keyBy('id');

            foreach ($request->receivers as $receiverData) {
                // Locations are stored as ids, as the shipment wizard stores them;
                // a name that matches nothing in the lists is kept as typed.
                $countryId = LocationLookup::countryIdForName($receiverData['country'] ?? null)
                    ?? LocationLookup::countryIdForPhone($receiverData['phone'] ?? null);
                $stateId = LocationLookup::stateIdForName($countryId, $receiverData['state_region'] ?? null);
                $cityId = LocationLookup::cityIdForName($stateId, $receiverData['city'] ?? null);

                $receiver = Receiver::create([
                    'shipment_id' => $shipment->id,
                    'receiver_name' => $receiverData['name'],
                    'receiver_phone' => $receiverData['phone'] ?? null,
                    'country' => $countryId ?? ($receiverData['country'] ?? null),
                    'state_region' => $stateId ?? ($receiverData['state_region'] ?? null),
                    'city' => $cityId ?? ($receiverData['city'] ?? null),
                    'address' => $receiverData['address'] ?? null,
                ]);

                foreach ($receiverData['items'] ?? [] as $itemData) {
                    $quantity = max(1, (int) ($itemData['quantity'] ?? 1));

                    ShipmentItem::create([
                        'shipment_id' => $shipment->id,
                        'receiver_id' => $receiver->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $quantity,
                        'box_no' => null,
                        // A line's value is the unit value times the quantity, as the shipment form stores it.
                        'item_cost' => round((float) ($products->get($itemData['product_id'])?->value ?? 0) * $quantity, 2),
                    ]);
                }
            }

            app(ShipmentCreationService::class)->finalise($shipment);

            $this->markConverted($request, $shipment, $approvedBy);

            return $shipment;
        });
    }

    /**
     * Reject a request and tell the client why (in-app notification plus push).
     * Shared by the Filament action and the staff API.
     */
    public function reject(ShipmentRequest $request, string $reason, User $rejectedBy): void
    {
        $request->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $rejectedBy->id,
            'reviewed_at' => now(),
        ]);

        ShipmentRequestSubmissionService::cancelPickup($request);

        $clientUser = User::where('client_id', $request->client_id)
            ->whereNotNull('client_id')
            ->first();

        if (! $clientUser) {
            return;
        }

        try {
            $clientUser->notify(new ShipmentRequestRejected($request));
            app(PushNotificationService::class)->sendToUser(
                $clientUser->id,
                'Shipment Request Not Approved',
                $reason,
                ['type' => 'shipment_request_rejected', 'request_id' => $request->id]
            );
        } catch (\Throwable) {
        }
    }

    /**
     * Link the request to the shipment made from it, mark it approved and tell the
     * client: an in-app notification plus the invoice by email. Shared by the quick
     * approval and the full shipment wizard.
     */
    public function markConverted(ShipmentRequest $request, Shipment $shipment, User $approvedBy): void
    {
        $request->update([
            'shipment_id' => $shipment->id,
            'status' => 'approved',
            'reviewed_by' => $approvedBy->id,
            'reviewed_at' => now(),
        ]);

        $client = $request->client ?? $request->load('client')->client;
        $shipment->load(['client:id,name,phone,email', 'receivers.items.product', 'payments']);

        $clientUser = User::where('client_id', $request->client_id)
            ->whereNotNull('client_id')
            ->first();

        if ($clientUser) {
            try {
                $clientUser->notify(new ShipmentRequestApproved($request, $shipment));
                app(PushNotificationService::class)->sendToUser(
                    $clientUser->id,
                    'Shipment Request Approved',
                    "Your request is now shipment {$shipment->shipping_reference}.",
                    [
                        'type' => 'shipment_request_approved',
                        'request_id' => $request->id,
                        'shipment_id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number,
                    ]
                );
            } catch (\Throwable) {
            }
        }

        // The shipment's `created` event fires ShipmentObserver, which sends the
        // client the tracking + payment-link email and SMS. This additionally emails
        // the signed invoice PDF.
        $paymentUrl = route('make-payment', $shipment->id);

        if ($client?->email) {
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
    }
}
