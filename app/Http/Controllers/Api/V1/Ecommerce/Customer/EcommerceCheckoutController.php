<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceOrderGroupResource;
use App\Services\EcommerceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceCheckoutController extends CustomerBaseController
{
    public function __construct(protected EcommerceOrderService $orderService) {}

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_address_id' => 'required|uuid',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $group = $this->orderService->createFromCart(
                auth()->user(),
                $data['delivery_address_id'],
                $data['notes'] ?? ''
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
