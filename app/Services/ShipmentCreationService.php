<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\ShipmentUpdate;

/**
 * The bookkeeping every new shipment needs, whichever screen created it: the
 * container reference and sequences, the invoice, the opening status-history row
 * and the payment status. Shared by the Filament wizard, the request approval and
 * the staff API so a shipment made on a phone matches one made in the admin.
 */
class ShipmentCreationService
{
    /**
     * @return array{shipping_reference: string, container_number: mixed, client_sequence: mixed, total_shipment_sequence: mixed}
     */
    public static function referenceFields(string $shipmentType = 'new', mixed $shippedAt = null, ?string $existingShipmentId = null): array
    {
        $refData = Shipment::generateShippingReference($shipmentType, $shippedAt ?? now(), null, $existingShipmentId);

        return [
            'shipping_reference' => $refData['reference'],
            'container_number' => $refData['container_number'] ?? null,
            'client_sequence' => $refData['client_sequence'] ?? null,
            'total_shipment_sequence' => $refData['total_shipment_sequence'] ?? null,
        ];
    }

    /**
     * Run after the shipment, its receivers, items and payments are saved.
     */
    public function finalise(Shipment $shipment): void
    {
        $shipment->load('payments');

        $total = (float) $shipment->total;
        $paid = (float) $shipment->payments->sum('amount');
        $status = $paid <= 0 ? 'pending' : ($paid >= $total && $total > 0 ? 'paid' : 'partial');

        $shipment->forceFill(['paid' => $paid, 'payment_status' => $status])->saveQuietly();

        if (! $shipment->invoice()->exists()) {
            Invoice::create([
                'shipment_id' => $shipment->id,
                'total_amount' => $shipment->total,
                'status' => match ($status) {
                    'paid' => 'paid',
                    'partial' => 'partial',
                    default => 'unpaid',
                },
            ]);
        }

        $statusValue = $shipment->status instanceof \BackedEnum ? $shipment->status->value : (string) $shipment->status;

        ShipmentUpdate::create([
            'shipment_id' => $shipment->id,
            'location' => $shipment->origin_branch_id ?? 'Origin',
            'status' => $statusValue,
            'remarks' => 'Shipment created - '.ucfirst($statusValue),
        ]);
    }
}
