<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\EcommerceProduct;
use Illuminate\Support\Facades\Cache;

/**
 * The KASAROSE MARKET slider on the public tracking page: marketplace products that are on
 * sale and auction lots that are live, each with a photo and a link to its page on the
 * storefront; with neither, in-stock priced products instead. Only public facts are
 * exposed — never the reserve price or stock levels.
 */
class PublicMarketShowcase
{
    public const LIMIT = 8;

    public const CACHE_SECONDS = 600;

    /**
     * @return list<array{kind: string, badge: string, name: string, image: string, price: string, was: ?string, note: ?string, url: string}>
     */
    public function slides(): array
    {
        $base = $this->marketUrl();

        if ($base === null) {
            return [];
        }

        return Cache::remember('site.market-showcase', self::CACHE_SECONDS, function () use ($base): array {
            try {
                $slides = $this->interleave($this->saleSlides($base), $this->auctionSlides($base));

                return $slides !== [] ? $slides : $this->catalogueSlides($base);
            } catch (\Throwable $e) {
                report($e);

                return [];
            }
        });
    }

    private function marketUrl(): ?string
    {
        $url = collect(config('group.companies'))->firstWhere('key', 'kasarose-market')['url'] ?? null;

        return $url ? rtrim($url, '/') : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function saleSlides(string $base): array
    {
        return EcommerceProduct::query()
            ->with('primaryImage')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereNotNull('discount_price_ghs')
            ->whereColumn('discount_price_ghs', '<', 'price_ghs')
            ->has('images')
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(function (EcommerceProduct $product) use ($base): array {
                $image = $product->primaryImage->first() ?? $product->images()->first();
                $percent = (int) round((1 - (float) $product->discount_price_ghs / (float) $product->price_ghs) * 100);

                return [
                    'kind' => 'sale',
                    'badge' => $percent > 0 ? "{$percent}% off" : 'On sale',
                    'name' => (string) $product->name,
                    'image' => asset('storage/'.$image->path),
                    'price' => $this->money($product->discount_price_ghs),
                    'was' => $this->money($product->price_ghs),
                    'note' => null,
                    'url' => $base.'/product/'.$product->id,
                ];
            })
            ->all();
    }

    /**
     * When nothing is on sale or up for auction: in-stock, priced products, featured ones first,
     * so the banner still shows real goods rather than the static artwork.
     *
     * @return list<array<string, mixed>>
     */
    private function catalogueSlides(string $base): array
    {
        return EcommerceProduct::query()
            ->with('primaryImage')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->where('price_ghs', '>', 0)
            ->has('images')
            ->orderByDesc('is_featured')
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(function (EcommerceProduct $product) use ($base): array {
                $image = $product->primaryImage->first() ?? $product->images()->first();

                return [
                    'kind' => 'shop',
                    'badge' => $product->is_featured ? 'Featured' : 'Shop now',
                    'name' => (string) $product->name,
                    'image' => asset('storage/'.$image->path),
                    'price' => $this->money($product->price_ghs),
                    'was' => null,
                    'note' => null,
                    'url' => $base.'/product/'.$product->id,
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auctionSlides(string $base): array
    {
        return Auction::query()
            ->live()
            ->orderBy('ends_at')
            ->limit(self::LIMIT)
            ->get()
            ->filter(fn (Auction $auction): bool => $auction->imageUrls() !== [])
            ->map(fn (Auction $auction): array => [
                'kind' => 'auction',
                'badge' => 'Live auction',
                'name' => (string) $auction->title,
                'image' => $auction->imageUrls()[0],
                'price' => $this->money($auction->current_price_ghs ?? $auction->starting_price_ghs),
                'was' => null,
                'note' => 'Ends '.$auction->ends_at->format('M j, g:i A'),
                'url' => $base.'/auctions/'.$auction->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * Alternate sales and auctions so neither crowds the other out, capped at LIMIT.
     *
     * @param  list<array<string, mixed>>  $sales
     * @param  list<array<string, mixed>>  $auctions
     * @return list<array<string, mixed>>
     */
    private function interleave(array $sales, array $auctions): array
    {
        $merged = [];

        if ($sales === [] && $auctions === []) {
            return [];
        }

        foreach (range(0, max(count($sales), count($auctions)) - 1) as $i) {
            foreach ([$sales[$i] ?? null, $auctions[$i] ?? null] as $slide) {
                if ($slide !== null) {
                    $merged[] = $slide;
                }
            }
        }

        return array_slice($merged, 0, self::LIMIT);
    }

    private function money(mixed $amount): string
    {
        return 'GH₵ '.number_format((float) $amount, 2);
    }
}
