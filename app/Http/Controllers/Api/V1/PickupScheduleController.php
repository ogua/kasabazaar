<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StorePickupScheduleRequest;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\ShipmentResource;
use App\Models\PickupSchedule;
use App\Services\PickupConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupScheduleController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_pickup_schedule'), 403);

        $branchId  = $this->resolveBranch($request);
        $query     = PickupSchedule::where('branch_id', $branchId)
            ->with(['client:id,name,phone', 'assignedStaff:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->latest('scheduled_at')->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => $this->formatSchedule($p));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_pickup_schedule'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'client_id'       => 'required|uuid|exists:clients,id',
            'shipment_id'     => 'nullable|uuid|exists:shipments,id',
            'assigned_to'     => 'nullable|uuid|exists:staff,id',
            'scheduled_at'    => 'required|date',
            'pickup_location' => 'required|string|max:255',
            'contact_phone'   => 'nullable|string|max:30',
            'notes'           => 'nullable|string',
            'items_description' => 'nullable|string',
        ]);

        $schedule = PickupSchedule::create(array_merge(
            $request->only(['client_id', 'shipment_id', 'assigned_to', 'scheduled_at', 'pickup_location', 'contact_phone', 'notes', 'items_description']),
            ['branch_id' => $branchId, 'status' => 'scheduled']
        ));

        return $this->success($this->formatSchedule($schedule->load(['client', 'assignedStaff'])), 'Pickup schedule created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_pickup_schedule'), 403);

        $branchId = $this->resolveBranch($request);
        $schedule = PickupSchedule::where('branch_id', $branchId)
            ->with(['client', 'assignedStaff'])
            ->findOrFail($id);

        return $this->success($this->formatSchedule($schedule));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_pickup_schedule'), 403);

        $branchId = $this->resolveBranch($request);
        $schedule = PickupSchedule::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'assigned_to'     => 'nullable|uuid|exists:staff,id',
            'scheduled_at'    => 'sometimes|date',
            'pickup_location' => 'sometimes|string|max:255',
            'contact_phone'   => 'nullable|string|max:30',
            'status'          => 'nullable|string|max:50',
            'notes'           => 'nullable|string',
        ]);

        $schedule->update($request->only(['assigned_to', 'scheduled_at', 'pickup_location', 'contact_phone', 'status', 'notes', 'items_description']));

        return $this->success($this->formatSchedule($schedule->fresh(['client', 'assignedStaff'])));
    }

    public function convert(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('create_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $pickup   = PickupSchedule::where('branch_id', $branchId)->findOrFail($id);

        if ($pickup->status !== 'completed') {
            return $this->error('Only completed pickups can be converted to shipments.', 422);
        }

        if ($pickup->shipment_id) {
            return $this->error('This pickup has already been converted to a shipment.', 409);
        }

        try {
            $shipment = app(PickupConversionService::class)->convert($pickup, $request->user());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            (new ShipmentResource($shipment))->resolve(),
            'Pickup converted to shipment successfully.',
            201
        );
    }

    private function formatSchedule(PickupSchedule $p): array
    {
        return [
            'id'               => $p->id,
            'branch_id'        => $p->branch_id,
            'client_id'        => $p->client_id,
            'shipment_id'      => $p->shipment_id,
            'assigned_to'      => $p->assigned_to,
            'scheduled_at'     => $p->scheduled_at,
            'pickup_location'  => $p->pickup_location,
            'contact_phone'    => $p->contact_phone,
            'status'           => $p->status,
            'notes'            => $p->notes,
            'items_description'=> $p->items_description,
            'created_at'       => $p->created_at,
            'client'           => $p->client ? ['id' => $p->client->id, 'name' => $p->client->name, 'phone' => $p->client->phone] : null,
            'assigned_staff'   => $p->assignedStaff ? ['id' => $p->assignedStaff->id, 'name' => $p->assignedStaff->name] : null,
        ];
    }
}
