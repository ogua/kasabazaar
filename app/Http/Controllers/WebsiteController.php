<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Services\PublicShipmentTracking;
use App\Services\PublicWebsite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * The public website's data-driven pages: shipment tracking, news and the sitemap. The static
 * pages are plain Route::view() routes in routes/web.php.
 */
class WebsiteController extends Controller
{
    public function tracking(Request $request, PublicShipmentTracking $tracking): View
    {
        // A GET so results are linkable: emails, SMS and invoices point straight at
        // /tracking?ref=... ; `query` is the parameter the old site and the shipment
        // portal used.
        $ref = trim((string) ($request->query('ref') ?? $request->query('query') ?? ''));
        $shipment = $ref !== '' ? $tracking->find($ref) : null;

        $data = [
            'ref' => $ref,
            'searched' => $ref !== '',
            'shipment' => $shipment,
            'status' => null,
            'stages' => PublicShipmentTracking::STAGES,
            'byTrackingNumber' => false,
            'items' => [],
            'timeline' => [],
            'facts' => [],
            'stageDates' => [],
            'market' => collect(PublicWebsite::groupCompanies())->firstWhere('external', true),
        ];

        if ($shipment === null) {
            return view('site.tracking', $data);
        }

        $status = PublicShipmentTracking::statusMeta($tracking->rawStatus($shipment), $tracking->isReceived($shipment));
        $byTrackingNumber = $tracking->lookedUpByTrackingNumber($shipment, $ref);

        $origin = trim(($tracking->placeName($shipment, 'origin') ?? '').' '.($shipment->originBranch?->country ?? ''));
        $destination = trim(($tracking->placeName($shipment, 'destination') ?? '').' '.($shipment->destinationBranch?->country ?? ''));
        $shippedAt = $this->date($shipment->getRawOriginal('shipped_at'));
        $deliveredAt = $this->date($shipment->getRawOriginal('delivered_at'));

        return view('site.tracking', [
            ...$data,
            'status' => $status,
            'byTrackingNumber' => $byTrackingNumber,
            'items' => $byTrackingNumber ? $tracking->items($shipment) : [],
            'timeline' => $tracking->timeline($shipment),
            'stageDates' => [
                PublicShipmentTracking::STAGE_SHIPPED => $shippedAt?->format('M j, Y'),
                PublicShipmentTracking::STAGE_DELIVERY => $deliveredAt?->format('M j, Y'),
            ],
            'facts' => array_filter([
                'Origin' => $origin ?: null,
                'Destination' => $destination ?: null,
                // shipped_at is filled in when the shipment is booked, so only show it
                // once the shipment has actually reached that stage.
                'Shipped' => ($status['step'] ?? 0) >= PublicShipmentTracking::STAGE_SHIPPED ? $shippedAt?->format('M j, Y') : null,
                'Estimated Delivery' => $this->date($shipment->getRawOriginal('estimated_delivery_date'))?->format('M j, Y'),
                'Delivered' => $deliveredAt?->format('M j, Y g:i A'),
            ]),
        ]);
    }

    public function news(): View
    {
        $posts = Blog::query()
            ->with('category')
            ->where('status', 1)
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->get()
            ->map(fn (Blog $blog): array => $this->postData($blog))
            ->all();

        return view('site.news', ['posts' => $posts]);
    }

    public function newsDetail(string $slug): View
    {
        $blog = Blog::query()
            ->with('category')
            ->where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        $post = $this->postData($blog);

        return view('site.news-detail', [
            'post' => $post,
            // The post's own summary is a better meta description than anything generic.
            'metaDescription' => trim(strip_tags((string) $post['description'])) !== ''
                ? mb_substr(strip_tags($post['description']), 0, 300)
                : 'News from '.config('group.parent.name').'.',
        ]);
    }

    public function sitemap(): Response
    {
        $pages = collect([
            'home-page' => ['weekly', '1.0'],
            'our-services' => ['monthly', '0.9'],
            'our-tracking' => ['monthly', '0.9'],
            'quote' => ['monthly', '0.9'],
            'about-us' => ['monthly', '0.8'],
            'our-companies' => ['monthly', '0.8'],
            'contact-us' => ['monthly', '0.8'],
            'our-blog' => ['weekly', '0.7'],
            'our-projects' => ['monthly', '0.6'],
            'faq' => ['monthly', '0.6'],
            'feedback' => ['yearly', '0.4'],
            'delivery-policy' => ['yearly', '0.4'],
            'privacy-policy' => ['yearly', '0.3'],
            'terms' => ['yearly', '0.3'],
            'sms-terms' => ['yearly', '0.3'],
        ])->map(fn (array $meta, string $name): array => [
            'loc' => route($name === 'feedback' ? 'our-feeback' : $name),
            'lastmod' => null,
            'changefreq' => $meta[0],
            'priority' => $meta[1],
        ])->values();

        $posts = Blog::query()
            ->where('status', 1)
            ->get(['slug', 'updated_at'])
            ->map(fn (Blog $blog): array => [
                'loc' => route('blog-details', $blog->slug),
                'lastmod' => $this->date($blog->getRawOriginal('updated_at'))?->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ]);

        return response()
            ->view('site.sitemap', ['pages' => $pages->concat($posts)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @return array{slug: string, title: string, description: ?string, content: ?string, image: string, image_is_upload: bool, category: string, postedby: ?string, references: ?string, keywords: ?string, posted_at: ?Carbon, updated_at: ?Carbon}
     */
    private function postData(Blog $blog): array
    {
        $image = trim((string) $blog->img);

        return [
            'slug' => $blog->slug,
            'title' => $blog->title,
            'description' => $blog->description,
            'content' => $blog->content,
            'image' => match (true) {
                $image === '' => asset('site/images/gallery/shipping-01.jpg'),
                (bool) preg_match('#^https?://#i', $image) => $image,
                default => Storage::disk('public')->url($image),
            },
            'image_is_upload' => $image !== '',
            'category' => (string) ($blog->category?->cat ?? ''),
            'postedby' => $blog->postedby,
            'references' => $blog->references,
            'keywords' => $blog->keywords,
            'posted_at' => $this->date($blog->getRawOriginal('published_at') ?: $blog->getRawOriginal('created_at')),
            'updated_at' => $this->date($blog->getRawOriginal('updated_at')),
        ];
    }

    private function date(mixed $value): ?Carbon
    {
        if (blank($value) || str_starts_with((string) $value, '0000')) {
            return null;
        }

        return Carbon::parse($value);
    }
}
