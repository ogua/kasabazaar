<?php

namespace App\Http\Controllers;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Type-ahead for the header search box. Proxies the backend's suggest
 * endpoint with a short cache, so popular prefixes cost one API call a
 * minute, and degrades to an empty result rather than an error.
 */
class SearchSuggestController extends Controller
{
    public function __invoke(Request $request, ProductsApi $productsApi): JsonResponse
    {
        $term = Str::of((string) $request->query('q'))->squish()->limit(100, '')->toString();

        if (Str::length($term) < 2) {
            return response()->json(['products' => [], 'categories' => [], 'auctions' => []]);
        }

        try {
            $result = Cache::remember('storefront.suggest.'.md5(Str::lower($term)), 60, fn () => $productsApi->suggest($term));
        } catch (KasabazaarApiException) {
            $result = [];
        }

        return response()->json([
            'products' => array_map(fn (array $p) => $p + ['url' => route('storefront.product', $p['id'])], $result['products'] ?? []),
            'categories' => array_map(fn (array $c) => ['name' => $c['name'], 'url' => route('storefront.category', $c['id'])], $result['categories'] ?? []),
            'auctions' => array_map(fn (array $a) => $a + ['url' => route('storefront.auction', $a['slug'])], $result['auctions'] ?? []),
        ]);
    }
}
