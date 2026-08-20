<?php

namespace App\Http\Controllers;

use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function __invoke(ProductsApi $productsApi)
    {
        $xml = Cache::remember('storefront.sitemap', 3600, function () use ($productsApi) {
            $sitemap = Sitemap::create()
                ->add(Url::create(route('storefront.home'))->setPriority(1.0))
                ->add(Url::create(route('storefront.shop'))->setPriority(0.9))
                ->add(Url::create(route('storefront.vendors'))->setPriority(0.7))
                ->add(Url::create(route('storefront.become-vendor'))->setPriority(0.5))
                ->add(Url::create(route('storefront.about'))->setPriority(0.4))
                ->add(Url::create(route('storefront.group'))->setPriority(0.4))
                ->add(Url::create(route('storefront.contact'))->setPriority(0.3))
                ->add(Url::create(route('storefront.faq'))->setPriority(0.3))
                ->add(Url::create(route('storefront.privacy'))->setPriority(0.2))
                ->add(Url::create(route('storefront.terms'))->setPriority(0.2))
                ->add(Url::create(route('storefront.delivery-policy'))->setPriority(0.2))
                ->add(Url::create(route('storefront.returns'))->setPriority(0.2))
                ->add(Url::create(route('storefront.cookies'))->setPriority(0.2));

            foreach ($productsApi->categories() as $category) {
                $sitemap->add(Url::create(route('storefront.category', $category['id']))->setPriority(0.6));
            }

            $page = 1;
            do {
                $response = $productsApi->list(['page' => $page, 'per_page' => 100]);

                foreach ($response->data as $product) {
                    $sitemap->add(Url::create(route('storefront.product', $product['id']))->setPriority(0.8));
                }

                $lastPage = $response->meta['last_page'] ?? 1;
                $page++;
            } while ($page <= $lastPage && $page <= 50); // hard cap to avoid runaway loops on huge catalogs

            foreach ($productsApi->vendors(['per_page' => 100])->data as $vendor) {
                $sitemap->add(Url::create(route('storefront.vendor', $vendor['slug']))->setPriority(0.6));
            }

            return $sitemap->render();
        });

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
