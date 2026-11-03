<?php

namespace App\Services;

use App\Models\ContainerClearance;
use App\Models\Shipment;
use App\Models\ShipmentContainer;

/**
 * Records a container's customs clearance: the container's cleared flag, the
 * clearing agent and an audit row, and (when the state changes) an alert to
 * every client with a shipment in it. Shared by the shipments list action in
 * the admin and the staff app.
 */
class ContainerClearanceService
{
    public function record(
        int $containerNumber,
        bool $isCleared,
        ?string $clearingAgentId,
        ?string $review,
        bool $notifyClients,
        ?string $recordedBy,
    ): ShipmentContainer {
        $year = null;
        $shipment = Shipment::query()
            ->where('container_number', '=', $containerNumber)
            ->whereNotNull('shipping_reference')
            ->first();

        if ($shipment?->shipping_reference) {
            $year = Shipment::parseShippingReference($shipment->shipping_reference)['year'] ?? null;
        }

        $previous = (bool) ShipmentContainer::query()
            ->where('container_number', '=', $containerNumber)
            ->value('is_cleared');

        $clearedAt = $isCleared ? now() : null;

        $container = ShipmentContainer::updateOrCreate(
            ['container_number' => $containerNumber],
            [
                'container_year' => $year,
                'is_cleared' => $isCleared,
                'review' => $review,
                'clearing_agent_id' => $clearingAgentId,
                'cleared_at' => $clearedAt,
            ]
        );

        if ($clearingAgentId) {
            ContainerClearance::create([
                'container_number' => $containerNumber,
                'container_year' => $year,
                'clearing_agent_id' => $clearingAgentId,
                'is_cleared' => $isCleared,
                'review' => $review,
                'recorded_by' => $recordedBy,
                'cleared_at' => $clearedAt ?? now(),
            ]);
        }

        // Clients are only told when the clearance state actually changes.
        if ($notifyClients && $previous !== $isCleared) {
            $event = $isCleared ? 'container_cleared' : 'container_update';

            Shipment::query()
                ->where('container_number', '=', $containerNumber)
                ->get()
                ->each(fn (Shipment $s) => ShipmentNotifier::sent($s, $event, ['note' => $review]));
        }

        return $container;
    }
}
