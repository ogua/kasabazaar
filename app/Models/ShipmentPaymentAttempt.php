<?php

namespace App\Models;

use App\Enums\ShipmentPaymentAttemptStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A Paystack checkout started for a shipment. Created before the payer is sent to
 * Paystack so the result (callback, webhook or a manual verify) can be checked
 * against what was actually asked for: amount, currency and shipment.
 */
class ShipmentPaymentAttempt extends Model
{
    use HasUuids;

    /**
     * Not part of the payment record: the Paystack checkout URL, set by
     * ShipmentPaymentService::start() for the caller to redirect to.
     */
    public ?string $authorizationUrl = null;

    /**
     * Not part of the payment record: Paystack's access code for the checkout, for
     * clients that open it with Paystack's own SDK.
     */
    public ?string $accessCode = null;

    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'fee_percent' => 'decimal:2',
            'fee_usd' => 'decimal:2',
            'amount_subunit' => 'integer',
            'exchange_rate' => 'decimal:4',
            'status' => ShipmentPaymentAttemptStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'KRP-'.Str::upper(Str::random(14));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * What the payer was charged in USD: the amount credited to the shipment plus
     * the transaction charge.
     */
    public function totalUsd(): float
    {
        return round((float) $this->amount_usd + (float) $this->fee_usd, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === ShipmentPaymentAttemptStatus::Success;
    }
}
