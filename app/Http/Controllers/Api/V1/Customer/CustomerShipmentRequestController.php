<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Requests\SaveCustomerShipmentRequest;
use App\Models\ShipmentRequest;
use App\Services\ShipmentRequestSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function store(SaveCustomerShipmentRequest $request): JsonResponse
    {
        $shipmentRequest = app(ShipmentRequestSubmissionService::class)->submit(
            $this->client(),
            $request->shipmentRequestData()
        );

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

    public function update(SaveCustomerShipmentRequest $request, string $id): JsonResponse
    {
        $shipmentRequest = ShipmentRequest::where('id', $id)
            ->where('client_id', $this->clientId())
            ->firstOrFail();

        if (! $shipmentRequest->isEditable()) {
            return $this->error('Only submitted or under-review requests can be edited.', 422);
        }

        $shipmentRequest = app(ShipmentRequestSubmissionService::class)->update($shipmentRequest, $request->shipmentRequestData());

        return $this->success($this->formatRequest($shipmentRequest), 'Request updated.');
    }

    public function cancel(string $id): JsonResponse
    {
        $shipmentRequest = ShipmentRequest::where('id', $id)
            ->where('client_id', $this->clientId())
            ->firstOrFail();

        if (! $shipmentRequest->isEditable()) {
            return $this->error('Only submitted or under-review requests can be cancelled.', 422);
        }

        app(ShipmentRequestSubmissionService::class)->cancel($shipmentRequest);

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
            'total' => array_sum($counts),
            'submitted' => $counts['submitted'] ?? 0,
            'under_review' => $counts['under_review'] ?? 0,
            'approved' => $counts['approved'] ?? 0,
            'rejected' => $counts['rejected'] ?? 0,
            'cancelled' => $counts['cancelled'] ?? 0,
        ]);
    }

    private function formatRequest(ShipmentRequest $r): array
    {
        $data = [
            'id' => $r->id,
            'status' => $r->status->value,
            'status_label' => $r->status->getLabel(),
            'is_editable' => $r->isEditable(),
            'source' => $r->source,
            'receivers' => $r->receivers,
            'items_total' => $r->itemsTotal(),
            'pickup_location' => $r->pickup_location,
            'preferred_pickup_at' => $r->preferred_pickup_at?->toISOString(),
            'notes' => $r->notes,
            'rejection_reason' => $r->rejection_reason,
            'shipment_id' => $r->shipment_id,
            'shipment' => null,
            'created_at' => $r->created_at?->toISOString(),
            'reviewed_at' => $r->reviewed_at?->toISOString(),
        ];

        if ($r->relationLoaded('shipment') && $r->shipment) {
            $data['shipment'] = [
                'id' => $r->shipment->id,
                'shipping_reference' => $r->shipment->shipping_reference,
                'tracking_number' => $r->shipment->tracking_number,
                'status' => $r->shipment->status instanceof \App\Enums\ShippingStatus
                    ? $r->shipment->status->value
                    : $r->shipment->status,
            ];
        }

        return $data;
    }
}
