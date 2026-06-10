<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ShipmentRequest;
use App\Services\ShipmentRequestApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentRequestController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = ShipmentRequest::with('client:id,name,phone')
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return $this->paginated($query->paginate(20), fn ($r) => $this->formatRequest($r));
    }

    public function show(string $id): JsonResponse
    {
        $shipmentRequest = ShipmentRequest::with(['client:id,name,phone,email', 'shipment:id,shipping_reference,tracking_number,status'])
            ->findOrFail($id);

        return $this->success($this->formatRequest($shipmentRequest));
    }

    public function markUnderReview(string $id): JsonResponse
    {
        $shipmentRequest = ShipmentRequest::findOrFail($id);

        if ($shipmentRequest->status->value !== 'submitted') {
            return $this->error('Only submitted requests can be marked under review.', 422);
        }

        $shipmentRequest->update(['status' => 'under_review']);

        return $this->success($this->formatRequest($shipmentRequest->fresh()), 'Marked as under review.');
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'shipping_cost'   => 'nullable|numeric|min:0',
            'vat_percentage'  => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        $shipmentRequest = ShipmentRequest::with('client')->findOrFail($id);

        if (!in_array($shipmentRequest->status->value, ['submitted', 'under_review'])) {
            return $this->error('Only submitted or under-review requests can be approved.', 422);
        }

        $service  = new ShipmentRequestApprovalService();
        $shipment = $service->approve(
            $shipmentRequest,
            auth()->user(),
            $request->only('shipping_cost', 'vat_percentage', 'notes')
        );

        return $this->success([
            'shipment_id'        => $shipment->id,
            'shipping_reference' => $shipment->shipping_reference,
        ], 'Request approved. Shipment created.');
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $shipmentRequest = ShipmentRequest::findOrFail($id);

        if ($shipmentRequest->status->value === 'approved') {
            return $this->error('Approved requests cannot be rejected.', 422);
        }

        $shipmentRequest->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        return $this->success(null, 'Request rejected.');
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
            'created_at'          => $r->created_at?->toISOString(),
            'reviewed_at'         => $r->reviewed_at?->toISOString(),
            'client'              => null,
            'shipment'            => null,
        ];

        if ($r->relationLoaded('client') && $r->client) {
            $data['client'] = [
                'id'    => $r->client->id,
                'name'  => $r->client->name,
                'phone' => $r->client->phone,
                'email' => $r->client->email,
            ];
        }

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
