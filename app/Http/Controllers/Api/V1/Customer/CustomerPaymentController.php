<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Payment;
use App\Models\Shipment;
use App\Services\ShipmentPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPaymentController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $paginated = Payment::whereHas('shipment', fn ($q) => $q->where('client_id', $this->clientId()))
            ->with(['shipment:id,shipping_reference'])
            ->latest('paid_on')
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => $this->formatPayment($p));
    }

    public function initiate(Request $request, ShipmentPaymentService $payments): JsonResponse
    {
        $request->validate([
            'shipment_id' => 'required|uuid|exists:shipments,id',
            'amount_usd' => 'required|numeric|min:0.01',
        ]);

        $shipment = Shipment::where('client_id', $this->clientId())->findOrFail($request->shipment_id);

        $balance = round($shipment->outstanding_balance, 2);
        if ((float) $request->amount_usd > $balance) {
            return $this->error('You can pay at most USD '.number_format($balance, 2).' — the outstanding balance.', 422);
        }

        try {
            $attempt = $payments->start(
                $shipment,
                (float) $request->amount_usd,
                // Bounces the in-app browser back into the mobile app when payment completes
                route('app-payment-complete'),
                'mobile_app',
                $request->user()->email,
            );
        } catch (\Throwable $e) {
            return $this->error($e->getMessage() ?: 'Failed to initiate payment.', 502);
        }

        return $this->success([
            'authorization_url' => $attempt->authorizationUrl,
            'reference' => $attempt->reference,
            'access_code' => $attempt->accessCode,
            'amount_usd' => (float) $attempt->amount_usd,
            'transaction_fee_usd' => (float) $attempt->fee_usd,
            'total_charge_usd' => $attempt->totalUsd(),
        ], 'Payment initiated.');
    }

    public function verify(Request $request, ShipmentPaymentService $payments): JsonResponse
    {
        $request->validate(['reference' => 'required|string|max:100']);

        // Verifies with Paystack, checks the charge against the checkout that was
        // started, and records it once (idempotent; the webhook may get there first).
        $payment = $payments->verify($request->reference);

        if (! $payment) {
            return $this->error('Payment verification failed.', 422);
        }

        if ($payment->shipment?->client_id !== $this->clientId()) {
            return $this->success(['reference' => $payment->payment_ref], 'Payment verified.');
        }

        if ($payment->user_id === null) {
            $payment->forceFill(['user_id' => auth()->id()])->saveQuietly();
        }

        return $this->success($this->formatPayment($payment->load('shipment')), 'Payment recorded successfully.');
    }

    private function formatPayment(Payment $p): array
    {
        return [
            'id' => $p->id,
            'shipment_id' => $p->shipment_id,
            'amount_usd' => $p->amount_usd,
            'amount_ghs' => $p->amount_ghs,
            'paying_method' => $p->paying_method,
            'payment_reference' => $p->payment_ref,
            'paid_on' => $p->paid_on,
            'shipment' => $p->shipment ? ['id' => $p->shipment->id, 'shipping_reference' => $p->shipment->shipping_reference] : null,
            'invoice_url' => $p->shipment_id ? url("/shipping-invoice-download/{$p->shipment_id}") : null,
            'receipt_url' => $p->shipment_id ? url("/shipping-receipt/{$p->shipment_id}") : null,
        ];
    }
}
