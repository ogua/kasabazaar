<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Trip;
use App\Models\TripShipment;
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

        return $this->paginated($paginated, fn ($t) => $this->formatTrip($t));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_trip'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'vehicle_id'          => 'required|uuid|exists:vehicles,id',
            'driver_id'           => 'required|uuid|exists:staff,id',
            'assistant_id'        => 'nullable|uuid|exists:staff,id',
            'origin'              => 'required|string|max:255',
            'destination'         => 'required|string|max:255',
            'route_description'   => 'nullable|string',
            'distance_km'         => 'nullable|numeric|min:0',
            'scheduled_date'      => 'required|date',
            'scheduled_departure' => 'nullable|date_format:Y-m-d H:i:s',
            'scheduled_arrival'   => 'nullable|date_format:Y-m-d H:i:s',
            'fuel_cost'           => 'nullable|numeric|min:0',
            'toll_fees'           => 'nullable|numeric|min:0',
            'driver_allowance'    => 'nullable|numeric|min:0',
            'other_costs'         => 'nullable|numeric|min:0',
            'start_mileage'       => 'nullable|integer|min:0',
            'notes'               => 'nullable|string',
        ]);

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
            $this->formatTrip($trip->load(['vehicle', 'driver', 'assistant'])),
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

        $data           = $this->formatTrip($trip);
        $data['shipments_summary'] = [
            'total'     => $trip->total_shipments,
            'delivered' => $trip->delivered_count,
        ];

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

        return $this->success($this->formatTrip($trip->fresh(['vehicle', 'driver', 'assistant'])));
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

    private function formatTrip(Trip $t): array
    {
        return [
            'id'                  => $t->id,
            'branch_id'           => $t->branch_id,
            'trip_reference'      => $t->trip_reference,
            'origin'              => $t->origin,
            'destination'         => $t->destination,
            'route_description'   => $t->route_description,
            'distance_km'         => $t->distance_km,
            'scheduled_date'      => $t->scheduled_date,
            'scheduled_departure' => $t->scheduled_departure,
            'scheduled_arrival'   => $t->scheduled_arrival,
            'actual_departure'    => $t->actual_departure,
            'actual_arrival'      => $t->actual_arrival,
            'status'              => $t->status instanceof \BackedEnum ? $t->status->value : $t->status,
            'fuel_cost'           => $t->fuel_cost,
            'toll_fees'           => $t->toll_fees,
            'driver_allowance'    => $t->driver_allowance,
            'other_costs'         => $t->other_costs,
            'total_cost'          => $t->total_cost,
            'start_mileage'       => $t->start_mileage,
            'end_mileage'         => $t->end_mileage,
            'notes'               => $t->notes,
            'created_at'          => $t->created_at,
            'vehicle'             => $t->vehicle ? ['id' => $t->vehicle->id, 'registration_number' => $t->vehicle->registration_number, 'make' => $t->vehicle->make, 'model' => $t->vehicle->model] : null,
            'driver'              => $t->driver ? ['id' => $t->driver->id, 'name' => $t->driver->name] : null,
            'assistant'           => $t->assistant ? ['id' => $t->assistant->id, 'name' => $t->assistant->name] : null,
        ];
    }
}
