<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'shipment_id' => 'required|uuid|exists:shipments,id',
            'amount_usd' => 'required|numeric|min:0.01',
        ]);

        $shipment = Shipment::where('client_id', $this->clientId())->findOrFail($request->shipment_id);

        $user = $request->user();
        $amountKobo = (int) round($request->amount_usd * 100); // Paystack uses smallest currency unit

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', [
                'email' => $user->email,
                'amount' => $amountKobo,
                'currency' => 'GHS',
                // Bounces the in-app browser back into the mobile app when payment completes
                'callback_url' => route('app-payment-complete'),
                'metadata' => [
                    'shipment_id' => $shipment->id,
                    'user_id' => $user->id,
                    'client_id' => $this->clientId(),
                    'amount_usd' => $request->amount_usd,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            return $this->error($response->json('message') ?? 'Failed to initiate payment.', 502);
        }

        return $this->success([
            'authorization_url' => $response->json('data.authorization_url'),
            'reference' => $response->json('data.reference'),
            'access_code' => $response->json('data.access_code'),
        ], 'Payment initiated.');
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate(['reference' => 'required|string']);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url').'/transaction/verify/'.$request->reference);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            return $this->error('Payment verification failed.', 422);
        }

        $data = $response->json('data');
        $metadata = $data['metadata'] ?? [];
        $shipmentId = $metadata['shipment_id'] ?? null;
        $amountUsd = $metadata['amount_usd'] ?? 0;

        if ($shipmentId) {
            $shipment = Shipment::where('client_id', $this->clientId())->find($shipmentId);

            if ($shipment) {
                $amountGhs = round($data['amount'] / 100, 2);

                // Idempotent — a repeated verify call must not double-record.
                $payment = Payment::firstOrNew(['payment_ref' => $data['reference']]);

                if (! $payment->exists) {
                    $payment->fill([
                        'shipment_id' => $shipmentId,
                        'branch_id' => $shipment->branch_id,
                        'payment_type' => 'credit',
                        'currency' => 'USD',
                        'amount' => $amountUsd,
                        'amount_usd' => $amountUsd,
                        'amount_ghs' => $amountGhs,
                        'paying_method' => $data['channel'] ?? 'paystack',
                        'paid_on' => now(),
                        'user_id' => auth()->id(),
                    ])->save();

                    $shipment->recalculatePaymentStatus();
                }

                return $this->success($this->formatPayment($payment->load('shipment')), 'Payment recorded successfully.');
            }
        }

        return $this->success(['reference' => $data['reference']], 'Payment verified.');
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
