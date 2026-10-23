<?php

namespace App\Jobs;

use App\Service\InvestmentPaymentService;
use App\Service\InvestmentTransferService;
use App\Services\EcommercePaymentService;
use App\Services\ShipmentPaymentService;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

/**
 * Processes a stored Paystack webhook call. Paystack sends every event for the
 * account to a single URL, so this routes each one to the part of the group that
 * started it: shipment payments, investment deposits, marketplace orders and
 * investment payout transfers. Every handler re-verifies with Paystack and is
 * idempotent, so a retried or duplicated event is harmless.
 */
class ProcessPaystackWebhookJob extends ProcessWebhookJob
{
    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 600];

    public function handle(
        ShipmentPaymentService $shipmentPayments,
        InvestmentPaymentService $investmentPayments,
        InvestmentTransferService $investmentTransfers,
        EcommercePaymentService $ecommercePayments,
    ): void {
        $event = $this->webhookCall->payload;
        $name = (string) ($event['event'] ?? '');

        if (str_starts_with($name, 'transfer.')) {
            $investmentTransfers->handlePaystackTransferEvent($event);

            return;
        }

        $reference = $event['data']['reference'] ?? null;

        if ($name !== 'charge.success' || blank($reference)) {
            return;
        }

        match (self::chargeType($event['data'])) {
            'shipment_payment' => $shipmentPayments->verify($reference),
            'investment_deposit' => $investmentPayments->handlePaystackDepositEvent($event),
            'ecommerce_order_group' => $ecommercePayments->verifyAndRecordPaystack($reference),
            default => null,
        };
    }

    /**
     * The metadata `type` set when the checkout was started. Shipment payments from
     * older mobile-app builds carry a `shipment_id` but no type.
     *
     * @param  array<string, mixed>  $data
     */
    public static function chargeType(array $data): ?string
    {
        $metadata = $data['metadata'] ?? [];

        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        if (! is_array($metadata)) {
            return null;
        }

        return $metadata['type'] ?? (isset($metadata['shipment_id']) ? 'shipment_payment' : null);
    }
}
