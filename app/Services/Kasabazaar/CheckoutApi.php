<?php

namespace App\Services\Kasabazaar;

class CheckoutApi
{
    public function __construct(protected KasabazaarClient $client) {}

    public function checkout(string $deliveryAddressId, string $notes = ''): array
    {
        return $this->client->post('marketplace/checkout', [
            'delivery_address_id' => $deliveryAddressId,
            'notes' => $notes,
        ])->data;
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
