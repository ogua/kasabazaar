<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\PickupSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPickupController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = PickupSchedule::where('client_id', $this->clientId())
            ->latest('scheduled_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => $this->formatPickup($p));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id'         => 'required|uuid|exists:branches,id',
            'scheduled_at'      => 'required|date|after:now',
            'pickup_location'   => 'required|string|max:255',
            'contact_phone'     => 'nullable|string|max:30',
            'notes'             => 'nullable|string',
            'items_description' => 'nullable|string',
        ]);

        $pickup = PickupSchedule::create([
            'client_id'         => $this->clientId(),
            'branch_id'         => $request->branch_id,
            'scheduled_at'      => $request->scheduled_at,
            'pickup_location'   => $request->pickup_location,
            'contact_phone'     => $request->contact_phone,
            'notes'             => $request->notes,
            'items_description' => $request->items_description,
            'status'            => 'scheduled',
        ]);

        return $this->success($this->formatPickup($pickup), 'Pickup scheduled successfully.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $pickup = PickupSchedule::where('client_id', $this->clientId())->findOrFail($id);
        return $this->success($this->formatPickup($pickup));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $pickup = PickupSchedule::where('client_id', $this->clientId())->findOrFail($id);

        if (!in_array($pickup->status, ['scheduled'])) {
            return $this->error('Only scheduled pickups can be modified.', 422);
        }

        $request->validate([
            'scheduled_at'      => 'sometimes|date|after:now',
            'pickup_location'   => 'sometimes|string|max:255',
            'contact_phone'     => 'nullable|string|max:30',
            'notes'             => 'nullable|string',
            'items_description' => 'nullable|string',
        ]);

        $pickup->update($request->only(['scheduled_at', 'pickup_location', 'contact_phone', 'notes', 'items_description']));

        return $this->success($this->formatPickup($pickup->fresh()));
    }

    public function destroy(string $id): JsonResponse
    {
        $pickup = PickupSchedule::where('client_id', $this->clientId())->findOrFail($id);

        if (!in_array($pickup->status, ['scheduled'])) {
            return $this->error('Only scheduled pickups can be cancelled.', 422);
        }

        $pickup->update(['status' => 'cancelled']);

        return $this->success(null, 'Pickup cancelled.');
    }

    private function formatPickup(PickupSchedule $p): array
    {
        return [
            'id'                => $p->id,
            'branch_id'         => $p->branch_id,
            'scheduled_at'      => $p->scheduled_at,
            'pickup_location'   => $p->pickup_location,
            'contact_phone'     => $p->contact_phone,
            'status'            => $p->status,
            'notes'             => $p->notes,
            'items_description' => $p->items_description,
            'shipment_id'       => $p->shipment_id,
            'converted_at'      => $p->converted_at,
            'created_at'        => $p->created_at,
        ];
    }
}
