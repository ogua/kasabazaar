<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Trip;
use App\Models\TripShipment;
use App\Http\Requests\StoreTripRequest;
use App\Http\Resources\TripResource;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_trip'), 403);

        $branchId = $this->resolveBranch($request);
        $query    = Trip::where('branch_id', $branchId)
            ->with(['vehicle:id,registration_number,make,model', 'driver:id,name', 'assistant:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('scheduled_date', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('scheduled_date', '<=', $to);
        }

        $paginated = $query->latest('scheduled_date')->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($t) => new TripResource($t));
    }

    public function store(StoreTripRequest $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $trip = Trip::create(array_merge(
            $request->only([
                'vehicle_id', 'driver_id', 'assistant_id', 'origin', 'destination',
                'route_description', 'distance_km', 'scheduled_date', 'scheduled_departure',
                'scheduled_arrival', 'fuel_cost', 'toll_fees', 'driver_allowance',
                'other_costs', 'start_mileage', 'notes',
            ]),
            ['branch_id' => $branchId, 'status' => 'planned']
        ));

        return $this->success(
            new TripResource($trip->load(['vehicle', 'driver', 'assistant'])),
            'Trip created.',
            201
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_trip'), 403);

        $branchId = $this->resolveBranch($request);
        $trip     = Trip::where('branch_id', $branchId)
            ->with(['vehicle', 'driver', 'assistant'])
            ->findOrFail($id);

        $data = array_merge((new TripResource($trip))->resolve(), [
            'shipments_summary' => [
                'total'     => $trip->total_shipments,
                'delivered' => $trip->delivered_count,
            ],
        ]);

        return $this->success($data);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_trip'), 403);

        $branchId = $this->resolveBranch($request);
        $trip     = Trip::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'status'           => 'sometimes|in:planned,scheduled,loading,in_progress,completed,cancelled,delayed',
            'actual_departure' => 'nullable|date',
            'actual_arrival'   => 'nullable|date',
            'end_mileage'      => 'nullable|integer|min:0',
            'fuel_cost'        => 'nullable|numeric|min:0',
            'toll_fees'        => 'nullable|numeric|min:0',
            'driver_allowance' => 'nullable|numeric|min:0',
            'other_costs'      => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $trip->update($request->only([
            'status', 'actual_departure', 'actual_arrival', 'end_mileage',
            'fuel_cost', 'toll_fees', 'driver_allowance', 'other_costs', 'notes',
            'vehicle_id', 'driver_id', 'assistant_id',
        ]));

        return $this->success(new TripResource($trip->fresh(['vehicle', 'driver', 'assistant'])));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_trip'), 403);

        $branchId = $this->resolveBranch($request);
        $trip     = Trip::where('branch_id', $branchId)->findOrFail($id);
        $trip->delete();

        return $this->success(null, 'Trip deleted.');
    }

    public function shipments(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_trip'), 403);

        $branchId = $this->resolveBranch($request);
        $trip     = Trip::where('branch_id', $branchId)->findOrFail($id);

        $tripShipments = TripShipment::where('trip_id', $id)
            ->with(['shipment:id,shipping_reference,tracking_number,status,client_id', 'shipment.client:id,name,phone'])
            ->get()
            ->map(fn ($ts) => [
                'id'               => $ts->id,
                'shipment_id'      => $ts->shipment_id,
                'delivery_status'  => $ts->delivery_status instanceof \BackedEnum ? $ts->delivery_status->value : $ts->delivery_status,
                'delivery_notes'   => $ts->delivery_notes,
                'delivered_at'     => $ts->delivered_at,
                'receiver_signature' => $ts->receiver_signature,
                'shipment'         => $ts->shipment ? [
                    'id'                 => $ts->shipment->id,
                    'shipping_reference' => $ts->shipment->shipping_reference,
                    'tracking_number'    => $ts->shipment->tracking_number,
                    'status'             => $ts->shipment->status instanceof \BackedEnum ? $ts->shipment->status->value : $ts->shipment->status,
                    'client'             => $ts->shipment->client ? ['id' => $ts->shipment->client->id, 'name' => $ts->shipment->client->name, 'phone' => $ts->shipment->client->phone] : null,
                ] : null,
            ]);

        return $this->success($tripShipments);
    }

    public function assignShipment(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_trip'), 403);

        $branchId = $this->resolveBranch($request);
        $trip     = Trip::where('branch_id', $branchId)->findOrFail($id);

        $request->validate(['shipment_id' => 'required|uuid|exists:shipments,id']);

        $shipmentId = $request->input('shipment_id');

        $already = TripShipment::where('trip_id', $id)->where('shipment_id', $shipmentId)->exists();
        if ($already) {
            return $this->error('Shipment is already assigned to this trip.', 422);
        }

        $ts = TripShipment::create([
            'trip_id'         => $trip->id,
            'shipment_id'     => $shipmentId,
            'delivery_status' => 'pending',
        ]);

        return $this->success(['id' => $ts->id, 'shipment_id' => $ts->shipment_id, 'delivery_status' => 'pending'], 'Shipment assigned to trip.', 201);
    }

    public function removeShipment(Request $request, string $id, string $shipmentId): JsonResponse
    {
        abort_unless(auth()->user()->can('update_trip'), 403);

        $branchId = $this->resolveBranch($request);
        Trip::where('branch_id', $branchId)->findOrFail($id);

        TripShipment::where('trip_id', $id)->where('shipment_id', $shipmentId)->delete();

        return $this->success(null, 'Shipment removed from trip.');
    }

    public function updateDelivery(Request $request, string $id, string $shipmentId): JsonResponse
    {
        abort_unless(auth()->user()->can('update_trip'), 403);

        $branchId = $this->resolveBranch($request);
        Trip::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'delivery_status'    => 'required|in:pending,delivered,failed,partial',
            'delivery_notes'     => 'nullable|string',
            'delivered_at'       => 'nullable|date',
            'receiver_signature' => 'nullable|string',
        ]);

        $tripShipment = TripShipment::where('trip_id', $id)
            ->where('shipment_id', $shipmentId)
            ->firstOrFail();

        $tripShipment->update([
            'delivery_status'    => $request->delivery_status,
            'delivery_notes'     => $request->input('delivery_notes'),
            'delivered_at'       => $request->input('delivered_at', $request->delivery_status === 'delivered' ? now() : null),
            'receiver_signature' => $request->input('receiver_signature'),
        ]);

        // Sync shipment status if delivered
        if ($request->delivery_status === 'delivered') {
            $tripShipment->shipment()->update(['status' => 'delivered', 'delivered_at' => $tripShipment->delivered_at]);
        }

        return $this->success([
            'id'               => $tripShipment->id,
            'delivery_status'  => $tripShipment->delivery_status instanceof \BackedEnum ? $tripShipment->delivery_status->value : $tripShipment->delivery_status,
            'delivery_notes'   => $tripShipment->delivery_notes,
            'delivered_at'     => $tripShipment->delivered_at,
        ]);
    }

}
