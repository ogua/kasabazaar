<?php

namespace App\Services;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Enums\VendorTransactionType;
use App\Models\EcommerceOrderGroup;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\User;
use App\Models\VendorTransaction;
use App\Notifications\Ecommerce\EcommerceOrderPaymentConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;

class EcommercePaymentService
{
    public function __construct(protected PushNotificationService $pushService) {}

    public function getGateway(string $country): string
    {
        return stripos($country, 'ghana') !== false ? 'paystack' : 'stripe';
    }

    public function initiatePaystack(EcommerceOrderGroup $group, User $user, string $client = 'web'): array
    {
        $amountPesewas = (int) round($group->total_ghs * 100);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', [
                'email' => $user->email,
                'amount' => $amountPesewas,
                'currency' => 'GHS',
                'callback_url' => $this->resolveCallbackUrl($client),
                'metadata' => [
                    'type' => 'ecommerce_order_group',
                    'order_group_id' => $group->id,
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

    public function initiateStripe(EcommerceOrderGroup $group, User $user): array
    {
        $stripe = new StripeClient(config('services.stripe.secret_key'));

        $amountCents = (int) round($group->total_usd * 100);

        $intent = $stripe->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => 'usd',
            'receipt_email' => $user->email,
            'metadata' => [
                'type' => 'ecommerce_order_group',
                'order_group_id' => $group->id,
                'user_id' => $user->id,
            ],
        ]);

        return [
            'gateway' => 'stripe',
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }

    public function verifyAndRecordPaystack(string $reference): EcommerceOrderGroup
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url').'/transaction/verify/'.$reference);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            throw new \RuntimeException('Paystack payment verification failed.');
        }

        $metadata = $response->json('data.metadata') ?? [];
        $groupId = $metadata['order_group_id'] ?? null;

        $group = EcommerceOrderGroup::findOrFail($groupId);

        if ($group->payment_status === EcommerceOrderPaymentStatus::Paid) {
            return $group; // already processed (idempotency)
        }

        $this->markPaid($group, 'paystack', $reference);

        return $group->fresh();
    }

    public function verifyAndRecordStripe(string $paymentIntentId): EcommerceOrderGroup
    {
        $stripe = new StripeClient(config('services.stripe.secret_key'));
        $intent = $stripe->paymentIntents->retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            throw new \RuntimeException('Stripe payment not yet succeeded.');
        }

        $groupId = $intent->metadata['order_group_id'] ?? null;
        $group = EcommerceOrderGroup::findOrFail($groupId);

        if ($group->payment_status === EcommerceOrderPaymentStatus::Paid) {
            return $group;
        }

        $this->markPaid($group, 'stripe', $paymentIntentId);

        return $group->fresh();
    }

    private function resolveCallbackUrl(string $client): string
    {
        $url = config("ecommerce.checkout_callback_urls.{$client}");

        if (! $url) {
            throw new \RuntimeException("No checkout callback URL configured for client '{$client}'.");
        }

        return $url;
    }

    private function markPaid(EcommerceOrderGroup $group, string $gateway, string $reference): void
    {
        DB::transaction(function () use ($group, $gateway, $reference) {
            $group->update([
                'payment_status' => EcommerceOrderPaymentStatus::Paid->value,
                'payment_gateway' => $gateway,
                'payment_reference' => $reference,
            ]);

            foreach ($group->orders as $order) {
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

                $this->creditVendorWallet($order);

                $order->user->notify(new EcommerceOrderPaymentConfirmed($order));
            }
        });

        $user = $group->user;

        $this->pushService->sendToUser(
            $user->id,
            'Payment Confirmed',
            "Payment for order {$group->order_group_number} received.",
            ['type' => 'ecommerce_order_group', 'order_group_id' => $group->id]
        );
    }

    private function creditVendorWallet(\App\Models\EcommerceOrder $order): void
    {
        $vendor = $order->vendor;

        if (! $vendor) {
            return;
        }

        $wallet = $vendor->wallet()->firstOrCreate([]);

        $commission = round((float) $order->total_ghs * ((float) $vendor->commission_rate / 100), 2);
        $vendorAmount = round((float) $order->total_ghs - $commission, 2);

        $wallet->pending_balance_ghs = (float) $wallet->pending_balance_ghs + $vendorAmount;
        $wallet->lifetime_earnings_ghs = (float) $wallet->lifetime_earnings_ghs + $vendorAmount;
        $wallet->save();

        VendorTransaction::create([
            'vendor_id' => $vendor->id,
            'ecommerce_order_id' => $order->id,
            'type' => VendorTransactionType::SaleCredit->value,
            'amount_ghs' => $vendorAmount,
            'balance_after_ghs' => $wallet->pending_balance_ghs,
            'description' => "Sale credit for order {$order->order_number}.",
        ]);

        if ($commission > 0) {
            VendorTransaction::create([
                'vendor_id' => $vendor->id,
                'ecommerce_order_id' => $order->id,
                'type' => VendorTransactionType::CommissionFee->value,
                'amount_ghs' => -$commission,
                'balance_after_ghs' => $wallet->pending_balance_ghs,
                'description' => "Marketplace commission ({$vendor->commission_rate}%) for order {$order->order_number}.",
            ]);
        }
    }
}
