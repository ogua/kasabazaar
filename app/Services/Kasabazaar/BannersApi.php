<?php

namespace App\Services\Kasabazaar;

/**
 * Storefront banners are managed in the kasabazaar admin (E-Commerce →
 * Storefront Banners); this storefront only reads them.
 */
class BannersApi
{
    public function __construct(protected KasabazaarClient $client) {}

    /**
     * @return array<int, array{id: string, title: string, subtitle: ?string, eyebrow: ?string, cta_label: ?string, link_url: ?string, image_url: string}>
     */
    public function forPosition(string $position = 'homepage_hero'): array
    {
        return $this->client->get('marketplace/banners', ['position' => $position])->data;
    }
}
