<?php

namespace App\Services\Kasabazaar;

class CheckoutApi
{
    public function __construct(protected KasabazaarClient $client) {}

    public function checkout(string $deliveryAddressId, string $notes = '', bool $whatsappOptIn = false, string $deliveryMethod = 'delivery', ?string $pickupStationId = null): array
    {
        return $this->client->post('marketplace/checkout', [
            'delivery_address_id' => $deliveryAddressId,
            'notes' => $notes,
            'whatsapp_opt_in' => $whatsappOptIn,
            'delivery_method' => $deliveryMethod,
            'pickup_station_id' => $pickupStationId,
        ])->data;
    }

    /**
     * How the cart can be received (delivery, pickup, KASAROSE USA-to-Ghana shipping),
     * the items each method covers and its cost.
     *
     * @return array{total_items: int, methods: array<int, array<string, mixed>>}
     */
    public function deliveryOptions(string $addressId): array
    {
        return $this->client->get('marketplace/delivery-options', ['address_id' => $addressId])->data;
    }

    /**
     * Where customers can collect Pickup orders, optionally for one country (Ghana or USA).
     *
     * @return array<int, array{id: string, name: string, country: string, city: string, address: string, phone: ?string, opening_hours: ?string, instructions: ?string}>
     */
    public function pickupStations(?string $country = null): array
    {
        return $this->client->get('marketplace/pickup-stations', array_filter(['country' => $country]))->data;
    }

    public function initiatePayment(string $orderGroupId): array
    {
        return $this->client->post('marketplace/payments/initiate', [
            'order_group_id' => $orderGroupId,
            'client' => 'web',
        ])->data;
    }

    public function verifyPaystack(string $reference): array
    {
        return $this->client->post('marketplace/payments/verify/paystack', ['reference' => $reference])->data;
    }

    public function verifyStripe(string $paymentIntentId): array
    {
        return $this->client->post('marketplace/payments/verify/stripe', ['payment_intent_id' => $paymentIntentId])->data;
    }

    public function addresses(): array
    {
        return $this->client->get('marketplace/addresses')->data;
    }

    public function createAddress(array $data): array
    {
        return $this->client->post('marketplace/addresses', $data)->data;
    }

    public function updateAddress(string $id, array $data): array
    {
        return $this->client->put("marketplace/addresses/{$id}", $data)->data;
    }

    public function deleteAddress(string $id): void
    {
        $this->client->delete("marketplace/addresses/{$id}");
    }

    public function setDefaultAddress(string $id): void
    {
        $this->client->patch("marketplace/addresses/{$id}/default");
    }

    public function shippingMethods(string $addressId): array
    {
        return $this->client->get('marketplace/shipping-methods', ['address_id' => $addressId])->data;
    }
}
