<?php

namespace App\Support;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The marketplace category list used by the header, the mobile drawer and the
 * shop filters. Cached for a minute; a failed call is not cached, so the next
 * request retries, and until then pages render with an empty list.
 */
class StorefrontCategories
{
    public function __construct(protected ProductsApi $productsApi) {}

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function all(): array
    {
        try {
            return Cache::remember('storefront.header.categories', 60, fn () => $this->productsApi->categories());
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.categories: failed to load categories', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array{id: string, name: string}|null
     */
    public function find(?string $id): ?array
    {
        if (blank($id)) {
            return null;
        }

        return collect($this->all())->firstWhere('id', $id);
    }
}
