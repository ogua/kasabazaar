<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_vehicle'), 403);

        $branchId = $this->resolveBranch($request);
        $query    = Vehicle::where('branch_id', $branchId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($v) => $this->formatVehicle($v));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('create_vehicle'), 403);

        $branchId = $this->resolveBranch($request);

        $request->validate([
            'registration_number' => 'required|string|max:30|unique:vehicles,registration_number',
            'vehicle_type'        => 'nullable|string|max:100',
            'make'                => 'nullable|string|max:100',
            'model'               => 'nullable|string|max:100',
            'year'                => 'nullable|integer|min:1900',
            'color'               => 'nullable|string|max:50',
            'max_weight_kg'       => 'nullable|numeric|min:0',
            'max_volume_cbm'      => 'nullable|numeric|min:0',
            'status'              => 'nullable|in:available,in_use,maintenance,retired',
            'insurance_expiry'    => 'nullable|date',
            'roadworthy_expiry'   => 'nullable|date',
            'registration_expiry' => 'nullable|date',
            'last_service_date'   => 'nullable|date',
            'last_service_mileage'=> 'nullable|integer|min:0',
            'current_mileage'     => 'nullable|integer|min:0',
            'next_service_due'    => 'nullable|date',
            'notes'               => 'nullable|string',
        ]);

        $vehicle = Vehicle::create(array_merge(
            $request->only([
                'registration_number', 'vehicle_type', 'make', 'model', 'year', 'color',
                'max_weight_kg', 'max_volume_cbm', 'status', 'insurance_expiry',
                'roadworthy_expiry', 'registration_expiry', 'last_service_date',
                'last_service_mileage', 'current_mileage', 'next_service_due', 'notes',
            ]),
            ['branch_id' => $branchId]
        ));

        return $this->success($this->formatVehicle($vehicle), 'Vehicle created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_vehicle'), 403);

        $branchId = $this->resolveBranch($request);
        $vehicle  = Vehicle::where('branch_id', $branchId)->findOrFail($id);

        return $this->success($this->formatVehicle($vehicle));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_vehicle'), 403);

        $branchId = $this->resolveBranch($request);
        $vehicle  = Vehicle::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'registration_number' => 'sometimes|string|max:30|unique:vehicles,registration_number,' . $id,
            'status'              => 'nullable|in:available,in_use,maintenance,retired',
            'current_mileage'     => 'nullable|integer|min:0',
            'insurance_expiry'    => 'nullable|date',
            'roadworthy_expiry'   => 'nullable|date',
            'registration_expiry' => 'nullable|date',
            'last_service_date'   => 'nullable|date',
            'last_service_mileage'=> 'nullable|integer|min:0',
            'next_service_due'    => 'nullable|date',
        ]);

        $vehicle->update($request->only([
            'registration_number', 'vehicle_type', 'make', 'model', 'year', 'color',
            'max_weight_kg', 'max_volume_cbm', 'status', 'insurance_expiry',
            'roadworthy_expiry', 'registration_expiry', 'last_service_date',
            'last_service_mileage', 'current_mileage', 'next_service_due', 'notes',
        ]));

        return $this->success($this->formatVehicle($vehicle->fresh()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('delete_vehicle'), 403);

        $branchId = $this->resolveBranch($request);
        $vehicle  = Vehicle::where('branch_id', $branchId)->findOrFail($id);
        $vehicle->delete();

        return $this->success(null, 'Vehicle deleted.');
    }

    public function maintenances(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_vehicle'), 403);

        $branchId = $this->resolveBranch($request);
        Vehicle::where('branch_id', $branchId)->findOrFail($id);

        $records = VehicleMaintenance::where('vehicle_id', $id)
            ->latest('scheduled_date')
            ->get(['id', 'vehicle_id', 'maintenance_type', 'cost', 'scheduled_date', 'completed_date', 'notes']);

        return $this->success($records);
    }

    public function addMaintenance(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('create_vehicle'), 403);

        $branchId = $this->resolveBranch($request);
        Vehicle::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'maintenance_type' => 'required|string|max:100',
            'cost'             => 'nullable|numeric|min:0',
            'scheduled_date'   => 'required|date',
            'completed_date'   => 'nullable|date',
            'notes'            => 'nullable|string',
        ]);

        $record = VehicleMaintenance::create(array_merge(
            $request->only(['maintenance_type', 'cost', 'scheduled_date', 'completed_date', 'notes']),
            ['vehicle_id' => $id]
        ));

        return $this->success($record, 'Maintenance record added.', 201);
    }

    public function trips(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_trip'), 403);

        $branchId = $this->resolveBranch($request);
        $vehicle  = Vehicle::where('branch_id', $branchId)->findOrFail($id);

        $paginated = $vehicle->trips()
            ->with(['driver:id,name', 'assistant:id,name'])
            ->latest('scheduled_date')
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($t) => [
            'id'             => $t->id,
            'trip_reference' => $t->trip_reference,
            'origin'         => $t->origin,
            'destination'    => $t->destination,
            'scheduled_date' => $t->scheduled_date,
            'status'         => $t->status instanceof \BackedEnum ? $t->status->value : $t->status,
            'total_cost'     => $t->total_cost,
            'driver'         => $t->driver ? ['id' => $t->driver->id, 'name' => $t->driver->name] : null,
        ]);
    }

    private function formatVehicle(Vehicle $v): array
    {
        return [
            'id'                   => $v->id,
            'branch_id'            => $v->branch_id,
            'registration_number'  => $v->registration_number,
            'vehicle_type'         => $v->vehicle_type,
            'make'                 => $v->make,
            'model'                => $v->model,
            'year'                 => $v->year,
            'color'                => $v->color,
            'max_weight_kg'        => $v->max_weight_kg,
            'max_volume_cbm'       => $v->max_volume_cbm,
            'status'               => $v->status instanceof \BackedEnum ? $v->status->value : $v->status,
            'insurance_expiry'     => $v->insurance_expiry,
            'roadworthy_expiry'    => $v->roadworthy_expiry,
            'registration_expiry'  => $v->registration_expiry,
            'last_service_date'    => $v->last_service_date,
            'last_service_mileage' => $v->last_service_mileage,
            'current_mileage'      => $v->current_mileage,
            'next_service_due'     => $v->next_service_due,
            'notes'                => $v->notes,
            'created_at'           => $v->created_at,
        ];
    }
}
