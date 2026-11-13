<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\User;
use App\Notifications\Ecommerce\DeliveryFeePaid;
use App\Notifications\Ecommerce\DeliveryFeeQuoted;
use App\Notifications\Ecommerce\DeliveryPlanned;
use Illuminate\Support\Facades\Http;

/**
 * Delivery cost for orders that travel by vendor Delivery or KASAROSE shipping.
 * The cost is not calculated at checkout: the shipping team looks at the items,
 * enters the cost, and the customer — who bears it — is notified and pays it
 * separately. Once paid, the delivery team contacts the customer to plan delivery.
 */
class DeliveryFeeService
{
    public const PAYMENT_TYPE = 'delivery_fee';

    public const NOT_REQUIRED = 'not_required';

    public const AWAITING_QUOTE = 'awaiting_quote';

    public const QUOTED = 'quoted';

    public const PAID = 'paid';

    public function __construct(protected DeliveryOptionsService $deliveryOptions) {}

    /**
     * Whether an order of this method needs the shipping team to quote a cost.
     */
    public function requiresQuote(DeliveryMethod $method): bool
    {
        return $method !== DeliveryMethod::Pickup;
    }

    /**
     * A starting point for the person entering the cost: the weight-based KASAROSE
     * rate, or the default delivery fee. Staff confirm or change it; nothing is charged from it.
     */
    public function suggestedFee(EcommerceOrder $order): float
    {
        if ($order->delivery_method === DeliveryMethod::KasaroseShipping->value) {
            $order->loadMissing('items.product');

            return $this->deliveryOptions->kasaroseQuote(
                $order->items->map(fn ($i) => ['weight' => $i->product?->weight, 'quantity' => $i->quantity])
            )['fee_ghs'];
        }

        return (float) config('ecommerce.default_delivery_fee_ghs', 0);
    }

    /**
     * Record the cost entered by staff and tell the customer. Can be revised until the customer has paid.
     */
    public function quote(EcommerceOrder $order, float $amountGhs, User $staff, ?string $notes = null): EcommerceOrder
    {
        if (! in_array($order->delivery_fee_status, [self::AWAITING_QUOTE, self::QUOTED], true)) {
            throw new \RuntimeException('A delivery cost cannot be set on this order.');
        }

        $order->update([
            'delivery_fee_ghs' => round($amountGhs, 2),
            'delivery_fee_status' => self::QUOTED,
            'delivery_fee_quoted_by' => $staff->id,
            'delivery_fee_quoted_at' => now(),
            'delivery_fee_notes' => $notes,
        ]);

        $this->history($order, "Delivery cost of GHS {$order->delivery_fee_ghs} set by {$staff->name}. Awaiting customer payment.", $staff);

        $order->user->notify(new DeliveryFeeQuoted($order->fresh(['deliveryDetail'])));

        return $order;
    }

    /**
     * Open a Paystack checkout for the quoted delivery cost.
     *
     * @return array{authorization_url: string, reference: string}
     */
    public function initiate(EcommerceOrder $order, User $user, string $client = 'web'): array
    {
        if ($order->delivery_fee_status !== self::QUOTED) {
            throw new \RuntimeException('There is no delivery cost to pay on this order yet.');
        }

        $callback = config("ecommerce.checkout_callback_urls.{$client}");

        if (! $callback) {
            throw new \RuntimeException("No checkout callback URL configured for client '{$client}'.");
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', [
                'email' => $user->email,
                'amount' => (int) round((float) $order->delivery_fee_ghs * 100),
                'currency' => 'GHS',
                'callback_url' => $callback,
                'metadata' => [
                    'type' => self::PAYMENT_TYPE,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException($response->json('message') ?? 'Failed to start the delivery payment.');
        }

        $order->update(['delivery_fee_payment_reference' => $response->json('data.reference')]);

        return [
            'authorization_url' => $response->json('data.authorization_url'),
            'reference' => $response->json('data.reference'),
        ];
    }

    /**
     * Confirm the payment with Paystack itself. Safe to call twice (redirect and webhook).
     */
    public function verifyPaystack(string $reference): EcommerceOrder
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url').'/transaction/verify/'.$reference);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            throw new \RuntimeException('Delivery payment verification failed.');
        }

        $order = EcommerceOrder::findOrFail($response->json('data.metadata.order_id'));

        if ((int) $response->json('data.amount') < (int) round((float) $order->delivery_fee_ghs * 100)) {
            throw new \RuntimeException('The amount paid does not match the delivery cost.');
        }

        return $this->markPaid($order);
    }

    public function markPaid(EcommerceOrder $order): EcommerceOrder
    {
        if ($order->delivery_fee_status === self::PAID) {
            return $order;
        }

        $order->update(['delivery_fee_status' => self::PAID, 'delivery_fee_paid_at' => now()]);
        $this->history($order, 'Delivery cost paid by the customer. The delivery team will contact them to plan delivery.', $order->user);

        $order->user->notify(new DeliveryFeePaid($order->fresh(['deliveryDetail'])));

        return $order;
    }

    /**
     * Record that the delivery team contacted the customer and, optionally, the agreed delivery
     * time. The customer is told when a time is set.
     */
    public function planDelivery(EcommerceOrder $order, User $staff, ?\DateTimeInterface $scheduledAt, ?string $notes): EcommerceOrder
    {
        $order->update([
            'delivery_contacted_at' => $order->delivery_contacted_at ?? now(),
            'delivery_contacted_by' => $staff->id,
            'delivery_scheduled_at' => $scheduledAt,
            'delivery_plan_notes' => $notes,
        ]);

        $this->history($order, 'Delivery planned with the customer by '.$staff->name.($scheduledAt ? ' for '.$scheduledAt->format('j M Y, g:i a') : '').'.', $staff);

        if ($scheduledAt) {
            $order->user->notify(new DeliveryPlanned($order->fresh(['deliveryDetail'])));
        }

        return $order;
    }

    private function history(EcommerceOrder $order, string $note, ?User $by): void
    {
        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
            'notes' => $note,
            'created_by' => $by?->id,
        ]);
    }
}
