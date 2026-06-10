<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\PickupSchedule;
use App\Models\ShipmentRequest;
use App\Service\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CustomerShipmentRequestController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = ShipmentRequest::where('client_id', $this->clientId())->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return $this->paginated($query->paginate(15), fn ($r) => $this->formatRequest($r));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'receivers'                             => 'required|array|min:1',
            'receivers.*.name'                      => 'required|string|max:255',
            'receivers.*.phone'                     => 'required|string|max:30',
            'receivers.*.country'                   => 'nullable|string|max:100',
            'receivers.*.state_region'              => 'nullable|string|max:100',
            'receivers.*.city'                      => 'nullable|string|max:100',
            'receivers.*.address'                   => 'nullable|string',
            'receivers.*.items'                     => 'required|array|min:1',
            'receivers.*.items.*.description'       => 'required|string|max:255',
            'receivers.*.items.*.product_id'        => 'nullable|uuid|exists:products,id',
            'receivers.*.items.*.quantity'          => 'required|integer|min:1',
            'receivers.*.items.*.estimated_value'   => 'nullable|numeric|min:0',
            'pickup_location'                       => 'required|string|max:255',
            'preferred_pickup_at'                   => 'required|date|after:now',
            'notes'                                 => 'nullable|string',
        ]);

        $shipmentRequest = ShipmentRequest::create([
            'client_id'           => $this->clientId(),
            'status'              => 'submitted',
            'receivers'           => $request->receivers,
            'pickup_location'     => $request->pickup_location,
            'preferred_pickup_at' => $request->preferred_pickup_at,
            'notes'               => $request->notes,
        ]);

        $client = $this->client();

        // Auto-create pickup schedule
        try {
            PickupSchedule::create([
                'branch_id'         => $client?->branch_id,
                'client_id'         => $this->clientId(),
                'scheduled_at'      => $request->preferred_pickup_at,
                'pickup_location'   => $request->pickup_location,
                'contact_phone'     => $client?->phone,
                'notes'             => $request->notes,
                'status'            => 'scheduled',
                'items_description' => collect($request->receivers)
                    ->flatMap(fn ($r) => $r['items'] ?? [])
                    ->pluck('description')
                    ->implode(', '),
            ]);
        } catch (\Throwable) {}

        // Notify admin
        try {
            $adminEmail = config('app.admin_email');
            $adminPhone = config('app.admin_phone');

            if ($adminEmail) {
                Mail::send(
                    'emails.admin_shipment_request',
                    ['request' => $shipmentRequest, 'client' => $client],
                    fn ($m) => $m->to($adminEmail)
                        ->subject('New Shipment Request – ' . ($client?->name ?? 'Client'))
                );
            }

            if ($adminPhone) {
                NotificationService::sendSmsToSender(
                    $adminPhone,
                    "New shipment request from {$client?->name}. Pickup: {$request->pickup_location}. Check admin panel."
                );
            }
        } catch (\Throwable) {}

        return $this->success($this->formatRequest($shipmentRequest), 'Shipment request submitted.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $shipmentRequest = ShipmentRequest::where('id', $id)
            ->where('client_id', $this->clientId())
            ->with('shipment:id,shipping_reference,tracking_number,status')
            ->firstOrFail();

        return $this->success($this->formatRequest($shipmentRequest));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $shipmentRequest = ShipmentRequest::where('id', $id)
            ->where('client_id', $this->clientId())
            ->firstOrFail();

        if (!in_array($shipmentRequest->status->value, ['submitted', 'under_review'])) {
            return $this->error('Only submitted or under-review requests can be edited.', 422);
        }

        $request->validate([
            'receivers'                             => 'required|array|min:1',
            'receivers.*.name'                      => 'required|string|max:255',
            'receivers.*.phone'                     => 'required|string|max:30',
            'receivers.*.country'                   => 'nullable|string|max:100',
            'receivers.*.state_region'              => 'nullable|string|max:100',
            'receivers.*.city'                      => 'nullable|string|max:100',
            'receivers.*.address'                   => 'nullable|string',
            'receivers.*.items'                     => 'required|array|min:1',
            'receivers.*.items.*.description'       => 'required|string|max:255',
            'receivers.*.items.*.product_id'        => 'nullable|uuid|exists:products,id',
            'receivers.*.items.*.quantity'          => 'required|integer|min:1',
            'receivers.*.items.*.estimated_value'   => 'nullable|numeric|min:0',
            'pickup_location'                       => 'required|string|max:255',
            'preferred_pickup_at'                   => 'required|date|after:now',
            'notes'                                 => 'nullable|string',
        ]);

        $shipmentRequest->update([
            'receivers'           => $request->receivers,
            'pickup_location'     => $request->pickup_location,
            'preferred_pickup_at' => $request->preferred_pickup_at,
            'notes'               => $request->notes,
        ]);

        return $this->success($this->formatRequest($shipmentRequest->fresh()), 'Request updated.');
    }

    public function cancel(string $id): JsonResponse
    {
        $shipmentRequest = ShipmentRequest::where('id', $id)
            ->where('client_id', $this->clientId())
            ->firstOrFail();

        if (!in_array($shipmentRequest->status->value, ['submitted', 'under_review'])) {
            return $this->error('Only submitted or under-review requests can be cancelled.', 422);
        }

        $shipmentRequest->update([
            'status'           => 'rejected',
            'rejection_reason' => 'Cancelled by client',
            'reviewed_at'      => now(),
        ]);

        return $this->success(null, 'Request cancelled.');
    }

    public function stats(): JsonResponse
    {
        $counts = ShipmentRequest::where('client_id', $this->clientId())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return $this->success([
            'total'        => array_sum($counts),
            'submitted'    => $counts['submitted']    ?? 0,
            'under_review' => $counts['under_review'] ?? 0,
            'approved'     => $counts['approved']     ?? 0,
            'rejected'     => $counts['rejected']     ?? 0,
        ]);
    }

    private function formatRequest(ShipmentRequest $r): array
    {
        $data = [
            'id'                  => $r->id,
            'status'              => $r->status->value,
            'status_label'        => $r->status->getLabel(),
            'receivers'           => $r->receivers,
            'pickup_location'     => $r->pickup_location,
            'preferred_pickup_at' => $r->preferred_pickup_at?->toISOString(),
            'notes'               => $r->notes,
            'rejection_reason'    => $r->rejection_reason,
            'shipment_id'         => $r->shipment_id,
            'shipment'            => null,
            'created_at'          => $r->created_at?->toISOString(),
            'reviewed_at'         => $r->reviewed_at?->toISOString(),
        ];

        if ($r->relationLoaded('shipment') && $r->shipment) {
            $data['shipment'] = [
                'id'                 => $r->shipment->id,
                'shipping_reference' => $r->shipment->shipping_reference,
                'tracking_number'    => $r->shipment->tracking_number,
                'status'             => $r->shipment->status instanceof \App\Enums\ShippingStatus
                    ? $r->shipment->status->value
                    : $r->shipment->status,
            ];
        }

        return $data;
    }
}
