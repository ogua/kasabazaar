<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\DeliveryAddressResource;
use App\Models\DeliveryAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryAddressController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $addresses = DeliveryAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->get();

        return $this->success(DeliveryAddressResource::collection($addresses));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $isDefault = (bool) ($data['is_default'] ?? false);

        if ($isDefault) {
            DeliveryAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address = DeliveryAddress::create(array_merge($data, [
            'user_id' => auth()->id(),
            'is_default' => $isDefault,
        ]));

        return $this->success(new DeliveryAddressResource($address), 'Address saved.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $address = DeliveryAddress::where('user_id', auth()->id())->findOrFail($id);

        return $this->success(new DeliveryAddressResource($address));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $address = DeliveryAddress::where('user_id', auth()->id())->findOrFail($id);

        $data = $request->validate($this->rules(isUpdate: true));
        $isDefault = isset($data['is_default']) ? (bool) $data['is_default'] : null;

        if ($isDefault === true) {
            DeliveryAddress::where('user_id', auth()->id())
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        return $this->success(new DeliveryAddressResource($address), 'Address updated.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $address = DeliveryAddress::where('user_id', auth()->id())->findOrFail($id);
        $address->delete();

        return $this->success(null, 'Address deleted.');
    }

    public function setDefault(Request $request, string $id): JsonResponse
    {
        $userId = auth()->id();
        $address = DeliveryAddress::where('user_id', $userId)->findOrFail($id);

        DeliveryAddress::where('user_id', $userId)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return $this->success(new DeliveryAddressResource($address), 'Default address updated.');
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'full_name' => "{$required}|string|max:255",
            'phone' => "{$required}|string|max:30",
            'alternative_phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'country' => "{$required}|string|max:100",
            'region' => "{$required}|string|max:100",
            'city' => "{$required}|string|max:100",
            'suburb' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'digital_address' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'delivery_notes' => 'nullable|string|max:500',
            'is_default' => 'boolean',
        ];
    }
}
