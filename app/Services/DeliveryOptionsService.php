<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Models\DeliveryAddress;
use App\Models\EcommerceCartItem;
use App\Models\ExchangeRateLog;
use App\Models\Vendor;
use Illuminate\Support\Collection;

/**
 * Works out how each cart line can reach the buyer and what it costs.
 *
 * Items held in the USA can only travel with KASAROSE; every other item goes by
 * the buyer's chosen local method (vendor delivery or pickup). A single order
 * may therefore carry both, and is split per vendor and per method.
 */
class DeliveryOptionsService
{
    public function __construct(protected ShippingService $shippingService) {}

    /**
     * The method a line actually travels by, given the buyer's local choice.
     */
    public function methodFor(EcommerceCartItem $item, DeliveryMethod $localChoice): DeliveryMethod
    {
        return $item->product?->ships_from_usa ? DeliveryMethod::KasaroseShipping : $localChoice;
    }

    /**
     * KASAROSE USA-to-Ghana charge for the given lines. The lane is fixed, so
     * distance is built into the per-kg rate.
     *
     * @param  iterable<int, array{weight: mixed, quantity: int}>  $lines
     * @return array{weight_kg: float, fee_ghs: float, fee_usd: float}
     */
    public function kasaroseQuote(iterable $lines): array
    {
        $config = config('ecommerce.kasarose_shipping');

        $weight = 0.0;
        foreach ($lines as $line) {
            $unit = (float) ($line['weight'] ?: $config['default_weight_kg']);
            $weight += $unit * (int) $line['quantity'];
        }

        $weight = round($weight, 3);
        $usd = $weight > 0 ? round(max($weight * $config['rate_per_kg_usd'], $config['min_charge_usd']), 2) : 0.0;
        $rate = (float) (ExchangeRateLog::latest('created_at')->first()?->rate ?? 1);

        return ['weight_kg' => $weight, 'fee_usd' => $usd, 'fee_ghs' => round($usd * $rate, 2)];
    }

    /**
     * Shipping fee for one vendor's lines travelling by one method.
     *
     * @param  Collection<int, EcommerceCartItem>  $items
     * @return array{fee_ghs: float, weight_kg: ?float}
     */
    public function feeFor(DeliveryMethod $method, Vendor $vendor, DeliveryAddress $address, Collection $items, float $subtotalGhs): array
    {
        if ($method === DeliveryMethod::Pickup) {
            return ['fee_ghs' => 0.0, 'weight_kg' => null];
        }

        if ($method === DeliveryMethod::KasaroseShipping) {
            $quote = $this->kasaroseQuote($items->map(fn ($i) => ['weight' => $i->product?->weight, 'quantity' => $i->quantity]));

            return ['fee_ghs' => $quote['fee_ghs'], 'weight_kg' => $quote['weight_kg']];
        }

        return ['fee_ghs' => $this->shippingService->feeFor($vendor, $address, $subtotalGhs), 'weight_kg' => null];
    }

    /**
     * The checkout summary. Delivery and Pickup cover the items held in Ghana;
     * KASAROSE shipping can carry the whole cart. `total_fee_ghs` is the shipping
     * total for the whole cart if that method is chosen (items held in the USA
     * always travel with KASAROSE, whatever the choice).
     *
     * @param  Collection<int, EcommerceCartItem>  $items
     * @return array<string, mixed>
     */
    public function optionsFor(Collection $items, DeliveryAddress $address): array
    {
        $items->loadMissing('product.vendor');

        $usa = $items->filter(fn ($i) => $i->product?->ships_from_usa);
        $local = $items->reject(fn ($i) => $i->product?->ships_from_usa);

        $line = fn ($i) => ['weight' => $i->product?->weight, 'quantity' => $i->quantity];

        $allQuote = $items->isEmpty() ? null : $this->kasaroseQuote($items->map($line));
        $k = config('ecommerce.kasarose_shipping');

        return [
            'total_items' => $items->count(),
            'methods' => [
                [
                    'key' => DeliveryMethod::Delivery->value,
                    'label' => 'Delivery',
                    'items_covered' => $local->count(),
                    'selectable' => $local->isNotEmpty(),
                    'fee_mode' => 'quoted',
                    'description' => 'Our delivery team reviews your items and sets the delivery cost. You pay it after we notify you, then we contact you to plan the delivery.',
                ],
                [
                    'key' => DeliveryMethod::Pickup->value,
                    'label' => 'Pickup',
                    'items_covered' => $local->count(),
                    'selectable' => $local->isNotEmpty(),
                    'fee_mode' => 'free',
                    'description' => 'Collect your order from the vendor. No delivery fee.',
                ],
                [
                    'key' => DeliveryMethod::KasaroseShipping->value,
                    'label' => 'KASAROSE Shipping',
                    'items_covered' => $items->count(),
                    'weight_kg' => $allQuote['weight_kg'] ?? 0.0,
                    'selectable' => $items->isNotEmpty(),
                    'fee_mode' => 'quoted',
                    'description' => 'Shipped from the USA to Ghana by KASAROSE LOGISTICS. Our shipping team reviews your items and sets the cost by weight; you pay it after we notify you. Usually '.$k['min_days'].' to '.$k['max_days'].' days.',
                ],
            ],
        ];
    }
}
