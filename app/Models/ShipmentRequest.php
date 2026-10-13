<?php

namespace App\Models;

use App\Enums\ShipmentRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentRequest extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => ShipmentRequestStatus::class,
        'receivers' => 'array',
        'preferred_pickup_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Fill each item's name and value from its catalogue product so neither can be
     * set by the client. Items without a product (a "new product" the client typed
     * in) keep their typed name and are priced by staff.
     *
     * @param  array<int, array<string, mixed>>  $receivers
     * @return array<int, array<string, mixed>>
     */
    public static function normaliseReceivers(array $receivers): array
    {
        $productIds = collect($receivers)
            ->flatMap(fn ($receiver) => $receiver['items'] ?? [])
            ->pluck('product_id')
            ->filter()
            ->unique();

        $products = Product::whereIn('id', $productIds)->get(['id', 'name', 'value'])->keyBy('id');

        return collect($receivers)->map(function (array $receiver) use ($products): array {
            $receiver['items'] = collect($receiver['items'] ?? [])->map(function (array $item) use ($products): array {
                $product = $products->get($item['product_id'] ?? null);

                return [
                    'product_id' => $product?->id,
                    'description' => $product?->name ?? trim((string) ($item['description'] ?? '')),
                    'details' => filled($item['details'] ?? null) ? trim($item['details']) : null,
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'estimated_value' => $product
                        ? (float) $product->value
                        : (isset($item['estimated_value']) && $item['estimated_value'] !== '' ? (float) $item['estimated_value'] : null),
                ];
            })->values()->all();

            return $receiver;
        })->values()->all();
    }

    /**
     * Sum of the requested items' values (value × quantity), in USD.
     */
    public function itemsTotal(): float
    {
        return round((float) collect($this->receivers ?? [])
            ->flatMap(fn ($receiver) => $receiver['items'] ?? [])
            ->sum(fn ($item) => (float) ($item['estimated_value'] ?? 0) * (int) ($item['quantity'] ?? 1)), 2);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [ShipmentRequestStatus::submitted, ShipmentRequestStatus::under_review], true);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(ShipmentRequestLink::class, 'shipment_request_link_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
