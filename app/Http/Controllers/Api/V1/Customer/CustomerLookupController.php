<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\Receiver;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerLookupController extends CustomerBaseController
{
    public function countries(): JsonResponse
    {
        $countries = Country::orderBy('name')->get(['id', 'name', 'iso2']);
        return $this->success($countries);
    }

    public function states(Request $request): JsonResponse
    {
        $countryId = $request->input('country_id');
        if (!$countryId) {
            return $this->success([]);
        }
        $states = State::where('country_id', $countryId)->orderBy('name')->get(['id', 'name', 'country_code']);
        return $this->success($states);
    }

    public function cities(Request $request): JsonResponse
    {
        $stateId = $request->input('state_id');
        if (!$stateId) {
            return $this->success([]);
        }
        $cities = City::where('state_id', $stateId)->orderBy('name')->get(['id', 'name']);
        return $this->success($cities);
    }

    public function products(Request $request): JsonResponse
    {
        $client   = $this->client();
        $search   = $request->input('search');
        $products = Product::where(fn ($q) => $q
                ->whereNull('branch_id')
                ->orWhere('branch_id', $client?->branch_id)
            )
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'sku', 'category', 'value']);

        return $this->success($products);
    }

    public function previousReceivers(): JsonResponse
    {
        $clientId = $this->clientId();

        $rows = Receiver::whereHas('shipment', fn ($q) => $q->where('client_id', $clientId))
            ->select('receiver_name', 'receiver_phone', 'country', 'state_region', 'city', 'address')
            ->distinct()
            ->get()
            ->unique(fn ($r) => strtolower($r->receiver_name) . '-' . $r->receiver_phone)
            ->values();

        $resolve = function ($vals, $modelClass) {
            if ($vals->isEmpty()) return [[], []];
            $nums    = $vals->filter('is_numeric');
            $strs    = $vals->reject('is_numeric');
            $records = $modelClass::select('id', 'name')
                ->where(fn ($q) => $q
                    ->when($nums->isNotEmpty(), fn ($q) => $q->orWhereIn('id', $nums))
                    ->when($strs->isNotEmpty(), fn ($q) => $q->orWhereIn('name', $strs))
                )->get();
            return [$records->keyBy('id')->all(), $records->keyBy('name')->all()];
        };

        [$cById, $cByName]       = $resolve($rows->pluck('country')->filter()->unique(),       Country::class);
        [$sById, $sByName]       = $resolve($rows->pluck('state_region')->filter()->unique(),   State::class);
        [$cityById, $cityByName] = $resolve($rows->pluck('city')->filter()->unique(),           City::class);

        $pick = fn ($val, $byId, $byName) => is_numeric($val) ? ($byId[$val] ?? null) : ($byName[$val] ?? null);

        $result = $rows->map(fn ($r) => [
            'receiver_name'  => $r->receiver_name,
            'receiver_phone' => $r->receiver_phone,
            'country_id'     => $pick($r->country,      $cById,    $cByName)?->id,
            'country_name'   => $pick($r->country,      $cById,    $cByName)?->name,
            'state_id'       => $pick($r->state_region, $sById,    $sByName)?->id,
            'state_name'     => $pick($r->state_region, $sById,    $sByName)?->name,
            'city_id'        => $pick($r->city,         $cityById, $cityByName)?->id,
            'city_name'      => $pick($r->city,         $cityById, $cityByName)?->name,
            'address'        => $r->address,
        ]);

        return $this->success($result);
    }
}
