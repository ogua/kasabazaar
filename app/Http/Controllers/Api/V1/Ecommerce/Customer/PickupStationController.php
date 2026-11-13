<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Models\PickupStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupStationController extends CustomerBaseController
{
    /**
     * The stations where customers can collect orders. Pass `country` (Ghana or USA) to
     * list one country's stations, so buyers in the USA see the USA stations.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['country' => 'nullable|string|max:60']);

        $stations = PickupStation::pickupPoints()
            ->when($data['country'] ?? null, fn ($q, $country) => $q->where('country', $country))
            ->orderBy('country')->orderBy('sort_order')->orderBy('name')
            ->get();

        return $this->success($stations->map(fn (PickupStation $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'country' => $s->country,
            'region' => $s->region,
            'city' => $s->city,
            'address' => $s->fullAddress(),
            'phone' => $s->phone,
            'opening_hours' => $s->opening_hours,
            'instructions' => $s->instructions,
        ])->values());
    }
}
