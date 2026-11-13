<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Enums\DeliveryMethod;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceOrderGroupResource;
use App\Services\EcommerceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EcommerceCheckoutController extends CustomerBaseController
{
    public function __construct(protected EcommerceOrderService $orderService) {}

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_address_id' => 'required|uuid',
            'notes' => 'nullable|string|max:500',
            'whatsapp_opt_in' => 'sometimes|boolean',
            'delivery_method' => ['sometimes', Rule::enum(DeliveryMethod::class)],
            // Pickup orders are collected from one of our pickup stations, in Ghana or the USA.
            'pickup_station_id' => ['required_if:delivery_method,pickup', 'nullable', 'uuid', Rule::exists('pickup_stations', 'id')->where('is_active', true)->where('is_pickup_point', true)],
        ]);

        // Recorded before the order exists, so the order-placed notification can already go out on WhatsApp.
        if ($request->boolean('whatsapp_opt_in')) {
            auth()->user()->setWhatsappConsent(true);
        }

        try {
            $group = $this->orderService->createFromCart(
                auth()->user(),
                $data['delivery_address_id'],
                $data['notes'] ?? '',
                DeliveryMethod::from($data['delivery_method'] ?? DeliveryMethod::Delivery->value),
                $data['pickup_station_id'] ?? null
            );
        } catch (InsufficientStockException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            new EcommerceOrderGroupResource($group),
            'Order placed successfully.',
            201
        );
    }
}
