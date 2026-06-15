<?php

namespace App\Services;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\User;
use App\Notifications\Ecommerce\EcommerceOrderPaymentConfirmed;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;

class EcommercePaymentService
{
    public function __construct(protected PushNotificationService $pushService) {}

    public function getGateway(string $country): string
    {
        return stripos($country, 'ghana') !== false ? 'paystack' : 'stripe';
    }

    public function initiatePaystack(EcommerceOrder $order, User $user): array
    {
        $amountPesewas = (int) round($order->total_ghs * 100);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', [
                'email' => $user->email,
                'amount' => $amountPesewas,
                'currency' => 'GHS',
                'callback_url' => 'rdd-client://payment/complete',
                'metadata' => [
                    'type' => 'ecommerce_order',
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException($response->json('message') ?? 'Failed to initiate Paystack payment.');
        }

        return [
            'gateway' => 'paystack',
            'authorization_url' => $response->json('data.authorization_url'),
            'reference' => $response->json('data.reference'),
            'access_code' => $response->json('data.access_code'),
        ];
    }

    public function initiateStripe(EcommerceOrder $order, User $user): array
    {
        $stripe = new StripeClient(config('services.stripe.secret_key'));

        $amountCents = (int) round($order->total_usd * 100);

        $intent = $stripe->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => 'usd',
            'receipt_email' => $user->email,
            'metadata' => [
                'type' => 'ecommerce_order',
                'order_id' => $order->id,
                'user_id' => $user->id,
            ],
        ]);

        return [
            'gateway' => 'stripe',
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }

    public function verifyAndRecordPaystack(string $reference): EcommerceOrder
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url').'/transaction/verify/'.$reference);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            throw new \RuntimeException('Paystack payment verification failed.');
        }

        $metadata = $response->json('data.metadata') ?? [];
        $orderId = $metadata['order_id'] ?? null;

        $order = EcommerceOrder::findOrFail($orderId);

        if ($order->payment_status === EcommerceOrderPaymentStatus::Paid) {
            return $order; // already processed (idempotency)
        }

        $this->markPaid($order, 'paystack', $reference);

        return $order->fresh();
    }

    public function verifyAndRecordStripe(string $paymentIntentId): EcommerceOrder
    {
        $stripe = new StripeClient(config('services.stripe.secret_key'));
        $intent = $stripe->paymentIntents->retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            throw new \RuntimeException('Stripe payment not yet succeeded.');
        }

        $orderId = $intent->metadata['order_id'] ?? null;
        $order = EcommerceOrder::findOrFail($orderId);

        if ($order->payment_status === EcommerceOrderPaymentStatus::Paid) {
            return $order;
        }

        $this->markPaid($order, 'stripe', $paymentIntentId);

        return $order->fresh();
    }

    private function markPaid(EcommerceOrder $order, string $gateway, string $reference): void
    {
        $order->update([
            'payment_status' => EcommerceOrderPaymentStatus::Paid->value,
            'payment_gateway' => $gateway,
            'payment_reference' => $reference,
            'status' => EcommerceOrderStatus::Paid->value,
        ]);

        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => EcommerceOrderStatus::Paid->value,
            'notes' => "Payment confirmed via {$gateway}.",
            'created_by' => $order->user_id,
        ]);

        $user = $order->user;
        $user->notify(new EcommerceOrderPaymentConfirmed($order));

        $this->pushService->sendToUser(
            $user->id,
            'Payment Confirmed',
            "Payment for order {$order->order_number} received.",
            ['type' => 'ecommerce_order', 'order_id' => $order->id]
        );
    }
}
