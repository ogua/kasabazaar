<?php

namespace App\Service;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Shipment;
use App\Models\TripShipment;
use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Enums\DeliveryStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TripService
{
    /**
     * Create a new trip
     */
    public function createTrip(array $data, array $shipmentIds = []): Trip
    {
        $trip = Trip::create($data);

        // Mark vehicle as in use
        if ($trip->vehicle) {
            $trip->vehicle->update(['status' => VehicleStatus::InUse]);
        }

        // Attach shipments
        foreach ($shipmentIds as $shipmentId) {
            TripShipment::create([
                'trip_id' => $trip->id,
                'shipment_id' => $shipmentId,
                'delivery_status' => DeliveryStatus::Pending,
            ]);
        }

        return $trip->load(['vehicle', 'driver', 'shipments']);
    }

    /**
     * Start a trip
     */
    public function startTrip(Trip $trip): Trip
    {
        $trip->update([
            'status' => TripStatus::InProgress,
            'actual_departure' => now(),
        ]);

        // Update vehicle mileage if provided
        if ($trip->start_mileage && $trip->vehicle) {
            $trip->vehicle->update(['current_mileage' => $trip->start_mileage]);
        }

        return $trip->fresh();
    }

    /**
     * Complete a trip
     */
    public function completeTrip(Trip $trip, ?int $endMileage = null): Trip
    {
        $trip->update([
            'status' => TripStatus::Completed,
            'actual_arrival' => now(),
            'end_mileage' => $endMileage,
        ]);

        // Mark vehicle as available
        if ($trip->vehicle) {
            $trip->vehicle->update([
                'status' => VehicleStatus::Available,
                'current_mileage' => $endMileage ?? $trip->vehicle->current_mileage,
            ]);
        }

        return $trip->fresh();
    }

    /**
     * Cancel a trip
     */
    public function cancelTrip(Trip $trip, ?string $reason = null): Trip
    {
        $trip->update([
            'status' => TripStatus::Cancelled,
            'notes' => $trip->notes . "\nCancellation reason: " . ($reason ?? 'Not specified'),
        ]);

        // Mark vehicle as available
        if ($trip->vehicle) {
            $trip->vehicle->update(['status' => VehicleStatus::Available]);
        }

        return $trip->fresh();
    }

    /**
     * Update shipment delivery status
     */
    public function updateDeliveryStatus(
        TripShipment $tripShipment,
        DeliveryStatus $status,
        ?string $notes = null,
        ?string $signaturePath = null
    ): TripShipment {
        $data = [
            'delivery_status' => $status,
            'delivery_notes' => $notes,
        ];

        if ($status === DeliveryStatus::Delivered) {
            $data['delivered_at'] = now();
            $data['receiver_signature'] = $signaturePath;
        }

        $tripShipment->update($data);

        return $tripShipment->fresh();
    }

    /**
     * Get driver performance metrics
     */
    public function getDriverPerformance(string $driverId, Carbon $startDate, Carbon $endDate): array
    {
        $trips = Trip::where('driver_id', $driverId)
            ->whereBetween('scheduled_departure', [$startDate, $endDate])
            ->with('tripShipments')
            ->get();

        $completedTrips = $trips->where('status', TripStatus::Completed);
        $totalDeliveries = $completedTrips->sum(fn ($t) => $t->tripShipments->count());
        $successfulDeliveries = $completedTrips->sum(fn ($t) => $t->tripShipments->where('delivery_status', DeliveryStatus::Delivered)->count());

        $onTimeDeliveries = $completedTrips->filter(fn ($t) =>
            $t->actual_arrival && $t->scheduled_arrival && $t->actual_arrival <= $t->scheduled_arrival
        )->count();

        return [
            'total_trips' => $trips->count(),
            'completed_trips' => $completedTrips->count(),
            'cancelled_trips' => $trips->where('status', TripStatus::Cancelled)->count(),
            'total_deliveries' => $totalDeliveries,
            'successful_deliveries' => $successfulDeliveries,
            'failed_deliveries' => $totalDeliveries - $successfulDeliveries,
            'delivery_success_rate' => $totalDeliveries > 0
                ? round(($successfulDeliveries / $totalDeliveries) * 100, 2)
                : 0,
            'on_time_rate' => $completedTrips->count() > 0
                ? round(($onTimeDeliveries / $completedTrips->count()) * 100, 2)
                : 0,
            'total_distance_km' => $completedTrips->sum(fn ($t) => $t->end_mileage - $t->start_mileage),
            'total_cost' => $completedTrips->sum('total_cost'),
        ];
    }

    /**
     * Get vehicle utilization metrics
     */
    public function getVehicleUtilization(string $vehicleId, Carbon $startDate, Carbon $endDate): array
    {
        $trips = Trip::where('vehicle_id', $vehicleId)
            ->whereBetween('scheduled_departure', [$startDate, $endDate])
            ->get();

        $completedTrips = $trips->where('status', TripStatus::Completed);
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $daysInUse = $trips->groupBy(fn ($t) => $t->scheduled_departure->format('Y-m-d'))->count();

        return [
            'total_trips' => $trips->count(),
            'completed_trips' => $completedTrips->count(),
            'total_distance_km' => $completedTrips->sum(fn ($t) => $t->end_mileage - $t->start_mileage),
            'total_cost' => $completedTrips->sum('total_cost'),
            'fuel_cost' => $completedTrips->sum('fuel_cost'),
            'utilization_rate' => $totalDays > 0
                ? round(($daysInUse / $totalDays) * 100, 2)
                : 0,
            'avg_cost_per_trip' => $completedTrips->count() > 0
                ? round($completedTrips->sum('total_cost') / $completedTrips->count(), 2)
                : 0,
        ];
    }

    /**
     * Get available vehicles for a trip
     */
    public function getAvailableVehicles(?string $branchId = null): Collection
    {
        $query = Vehicle::where('status', VehicleStatus::Available);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Get pending shipments for trip assignment
     */
    public function getPendingShipmentsForTrip(?string $branchId = null): Collection
    {
        $query = Shipment::whereDoesntHave('trips')
            ->where('status', 'shipped');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->with('client', 'receivers')->get();
    }
}
