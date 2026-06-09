<?php

namespace App\Http\Controllers\Api\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret    = config('services.paystack.webhook_secret');
        $signature = $request->header('x-paystack-signature');

        if ($secret && $signature !== hash_hmac('sha512', $request->getContent(), $secret)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = $request->input('event');
        $data  = $request->input('data', []);

        if ($event === 'charge.success') {
            $this->handleChargeSuccess($data);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleChargeSuccess(array $data): void
    {
        $metadata   = $data['metadata'] ?? [];
        $shipmentId = $metadata['shipment_id'] ?? null;
        $amountUsd  = $metadata['amount_usd'] ?? 0;
        $reference  = $data['reference'] ?? null;

        if (!$shipmentId || !$reference) return;

        // Idempotency — skip if already recorded
        if (Payment::where('payment_reference', $reference)->exists()) return;

        $shipment = Shipment::find($shipmentId);
        if (!$shipment) return;

        $amountGhs = round(($data['amount'] ?? 0) / 100, 2);

        $payment = Payment::create([
            'shipment_id'       => $shipmentId,
            'branch_id'         => $shipment->branch_id,
            'amount_usd'        => $amountUsd,
            'amount_ghs'        => $amountGhs,
            'paying_method'     => 'paystack',
            'payment_reference' => $reference,
            'paid_on'           => now(),
        ]);

        $paidTotal = $shipment->payments()->sum('amount_usd');
        $shipment->update([
            'paid'           => $paidTotal,
            'payment_status' => $paidTotal >= $shipment->total ? 'paid' : 'partial',
        ]);
    }
}
