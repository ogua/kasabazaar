<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * The storefront's launch hero slider. Artwork lives in
 * database/seeders/assets/banners (text-free backgrounds; the storefront sets
 * the headline and button as live text) and is copied to the public disk.
 *
 * Idempotent: keyed on image_path, so re-running refreshes the files without
 * duplicating rows or overwriting copy that staff have since edited.
 *
 *   php artisan db:seed --class=BannerSeeder
 */
class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'file' => 'hero-auctions.jpg',
                'eyebrow' => 'Live auctions',
                'title' => 'Bid on one-of-a-kind finds.',
                'subtitle' => 'Place your bid, watch the clock, and win great items at the price you choose.',
                'cta_label' => 'Start bidding',
                'link_url' => '/auctions',
            ],
            [
                'file' => 'hero-home-living.jpg',
                'eyebrow' => 'Home & living',
                'title' => 'Furnish your home from local makers.',
                'subtitle' => 'Cabinets, storage and furniture from vetted vendors, delivered across Ghana.',
                'cta_label' => 'Shop home & living',
                'link_url' => '/shop',
            ],
            [
                'file' => 'hero-sell.jpg',
                'eyebrow' => 'For businesses',
                'title' => 'Open your store. We deliver for you.',
                'subtitle' => 'List your products, get paid securely and reach shoppers in every region.',
                'cta_label' => 'Become a vendor',
                'link_url' => '/become-a-vendor',
            ],
        ];

        foreach ($slides as $order => $slide) {
            $path = 'banners/'.$slide['file'];

            Storage::disk('public')->put($path, file_get_contents(database_path('seeders/assets/banners/'.$slide['file'])));

            Banner::firstOrCreate(
                ['image_path' => $path],
                [
                    'eyebrow' => $slide['eyebrow'],
                    'title' => $slide['title'],
                    'subtitle' => $slide['subtitle'],
                    'cta_label' => $slide['cta_label'],
                    'link_url' => $slide['link_url'],
                    'position' => 'homepage_hero',
                    'sort_order' => $order + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
