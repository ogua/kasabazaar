<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Staff;
use App\Models\Trip;
use App\Models\TripShipment;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints exclusively for the driver role.
 * A driver is a User linked to a Staff record with role code 'DRIVER'.
 */
class DriverController extends BaseApiController
{
    /**
     * Resolve the Staff record for the authenticated driver.
     * Aborts 403 if the user is not linked to a driver staff record.
     */
    private function resolveDriverStaff(): Staff
    {
        $staff = Staff::where('user_id', auth()->id())
            ->with('role')
            ->first();

        abort_unless($staff && $staff->isDriver(), 403, 'Only drivers can access this endpoint.');

        return $staff;
    }

    /**
     * GET /api/v1/driver/profile
     * Driver's own staff profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $staff = $this->resolveDriverStaff();

        return $this->success([
            'id'                => $staff->id,
            'branch_id'         => $staff->branch_id,
            'name'              => $staff->name,
            'email'             => $staff->email,
            'phone'             => $staff->phone,
            'position'          => $staff->position,
            'employee_id'       => $staff->employee_id,
            'employment_status' => $staff->employment_status instanceof \BackedEnum ? $staff->employment_status->value : $staff->employment_status,
            'role'              => $staff->role ? ['id' => $staff->role->id, 'name' => $staff->role->name] : null,
        ]);
    }

    /**
     * GET /api/v1/driver/trips
     * All trips assigned to the authenticated driver.
     */
    public function trips(Request $request): JsonResponse
    {
        $staff  = $this->resolveDriverStaff();
        $query  = Trip::where('driver_id', $staff->id)
            ->with(['vehicle:id,registration_number,make,model', 'branch:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->latest('scheduled_date')->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($t) => $this->formatDriverTrip($t));
    }

    /**
     * GET /api/v1/driver/trips/{id}
     * Single trip detail with full shipment list.
     */
    public function tripDetail(Request $request, string $id): JsonResponse
    {
        $staff = $this->resolveDriverStaff();
        $trip  = Trip::where('driver_id', $staff->id)
            ->with(['vehicle', 'branch:id,name', 'assistant:id,name'])
            ->findOrFail($id);

        $tripShipments = TripShipment::where('trip_id', $id)
            ->with([
                'shipment:id,shipping_reference,tracking_number,status,client_id,destination_branch_id',
                'shipment.client:id,name,phone',
                'shipment.destinationBranch:id,name',
                'shipment.receivers:id,shipment_id,receiver_name,receiver_phone,address,city,state_region',
            ])
            ->get()
            ->map(fn ($ts) => [
                'id'                 => $ts->id,
                'shipment_id'        => $ts->shipment_id,
                'delivery_status'    => $ts->delivery_status instanceof \BackedEnum ? $ts->delivery_status->value : $ts->delivery_status,
                'delivery_notes'     => $ts->delivery_notes,
                'delivered_at'       => $ts->delivered_at,
                'receiver_signature' => $ts->receiver_signature,
                'shipment'           => $ts->shipment ? [
                    'id'                  => $ts->shipment->id,
                    'shipping_reference'  => $ts->shipment->shipping_reference,
                    'tracking_number'     => $ts->shipment->tracking_number,
                    'status'              => $ts->shipment->status instanceof \BackedEnum ? $ts->shipment->status->value : $ts->shipment->status,
                    'client'              => $ts->shipment->client ? [
                        'id'    => $ts->shipment->client->id,
                        'name'  => $ts->shipment->client->name,
                        'phone' => $ts->shipment->client->phone,
                    ] : null,
                    'destination_branch'  => $ts->shipment->destinationBranch?->name,
                    'receivers'           => $ts->shipment->receivers?->map(fn ($r) => [
                        'id'            => $r->id,
                        'receiver_name' => $r->receiver_name,
                        'receiver_phone'=> $r->receiver_phone,
                        'address'       => $r->address,
                        'city'          => $r->city,
                        'state_region'  => $r->state_region,
                    ]),
                ] : null,
            ]);

        $data                     = $this->formatDriverTrip($trip);
        $data['shipments']        = $tripShipments;
        $data['shipments_total']  = $trip->total_shipments;
        $data['delivered_count']  = $trip->delivered_count;
        $data['delivery_rate']    = round($trip->delivery_rate, 1);

        return $this->success($data);
    }

    /**
     * PUT /api/v1/driver/trips/{id}/status
     * Driver updates trip status (start/end trip).
     */
    public function updateTripStatus(Request $request, string $id): JsonResponse
    {
        $staff = $this->resolveDriverStaff();
        $trip  = Trip::where('driver_id', $staff->id)->findOrFail($id);

        $request->validate([
            'status'           => 'required|in:loading,in_progress,completed',
            'actual_departure' => 'nullable|date',
            'actual_arrival'   => 'nullable|date',
            'end_mileage'      => 'nullable|integer|min:0',
            'notes'            => 'nullable|string',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'in_progress' && !$trip->actual_departure) {
            $data['actual_departure'] = $request->input('actual_departure', now());
        }

        if ($request->status === 'completed') {
            $data['actual_arrival'] = $request->input('actual_arrival', now());
            if ($request->has('end_mileage')) {
                $data['end_mileage'] = $request->end_mileage;
            }
        }

        if ($request->has('notes')) {
            $data['notes'] = $request->notes;
        }

        $trip->update($data);

        return $this->success($this->formatDriverTrip($trip->fresh(['vehicle', 'branch'])), 'Trip status updated.');
    }

    /**
     * PUT /api/v1/driver/trips/{id}/shipments/{shipmentId}
     * Driver marks a shipment as delivered/failed/partial.
     */
    public function updateDelivery(Request $request, string $id, string $shipmentId): JsonResponse
    {
        $staff = $this->resolveDriverStaff();
        Trip::where('driver_id', $staff->id)->findOrFail($id);

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

        // Sync shipment status on delivery
        if ($request->delivery_status === 'delivered') {
            $tripShipment->shipment()->update([
                'status'       => 'delivered',
                'delivered_at' => $tripShipment->delivered_at,
            ]);
        }

        return $this->success([
            'id'               => $tripShipment->id,
            'delivery_status'  => $tripShipment->delivery_status instanceof \BackedEnum ? $tripShipment->delivery_status->value : $tripShipment->delivery_status,
            'delivery_notes'   => $tripShipment->delivery_notes,
            'delivered_at'     => $tripShipment->delivered_at,
        ], 'Delivery status updated.');
    }

    /**
     * GET /api/v1/driver/schedule
     * Upcoming trips for the authenticated driver (today and future).
     */
    public function schedule(Request $request): JsonResponse
    {
        $staff = $this->resolveDriverStaff();

        $trips = Trip::where('driver_id', $staff->id)
            ->whereDate('scheduled_date', '>=', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['vehicle:id,registration_number,make,model', 'branch:id,name'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_departure')
            ->get()
            ->map(fn ($t) => $this->formatDriverTrip($t));

        return $this->success($trips);
    }

    private function formatDriverTrip(Trip $t): array
    {
        return [
            'id'                  => $t->id,
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
            'driver_allowance'    => $t->driver_allowance,
            'total_cost'          => $t->total_cost,
            'start_mileage'       => $t->start_mileage,
            'end_mileage'         => $t->end_mileage,
            'notes'               => $t->notes,
            'vehicle'             => $t->vehicle ? [
                'id'                  => $t->vehicle->id,
                'registration_number' => $t->vehicle->registration_number,
                'make'                => $t->vehicle->make,
                'model'               => $t->vehicle->model,
            ] : null,
            'branch'              => $t->branch ? ['id' => $t->branch->id, 'name' => $t->branch->name] : null,
        ];
    }
}
