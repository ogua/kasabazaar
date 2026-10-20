<?php

namespace App\Services;

/**
 * Shared data for the public website (resources/views/site), served on kasarose.com.
 *
 * Company names and sister-company URLs come from the group roster (config/group.php);
 * the site's own contact details, services and delivery time frames from config/website.php.
 */
class PublicWebsite
{
    /**
     * Variables every site.* view can use.
     *
     * @return array{siteServices: array<string, array<string, string>>, groupCompanies: list<array<string, mixed>>, deliveryTimeframes: list<array<string, string>>, marketUrl: ?string}
     */
    public static function viewData(): array
    {
        $companies = self::groupCompanies();

        return [
            'siteServices' => config('website.services'),
            'groupCompanies' => $companies,
            'deliveryTimeframes' => config('website.delivery_timeframes'),
            'marketUrl' => collect(config('group.companies'))->firstWhere('key', 'kasarose-market')['url'] ?? null,
        ];
    }

    /**
     * The companies shown on the site, parent first: the parent's own card, then every
     * sister company in the roster that is not hidden with `show_on_website` => false.
     *
     * @return list<array{name: string, tagline: ?string, description: string, url: string, url_label: string, external: bool, logo: ?string}>
     */
    public static function groupCompanies(): array
    {
        $parent = config('group.parent');

        $sisters = collect(config('group.companies'))
            ->filter(fn (array $company): bool => $company['show_on_website'] ?? true)
            ->map(fn (array $company): array => [
                'name' => $company['name'],
                'tagline' => $company['tagline'] ?? $company['role'],
                'description' => $company['description'],
                'url' => $company['url'],
                'url_label' => 'Visit '.$company['url_label'],
                'external' => true,
                'logo' => $company['logo'] ?? null,
            ]);

        return collect([[
            'name' => $parent['name'],
            'tagline' => $parent['tagline'],
            'description' => $parent['description'],
            'url' => route('our-services'),
            'url_label' => 'View Our Services',
            'external' => false,
            'logo' => $parent['logo'],
        ]])->concat($sisters)->values()->all();
    }

    /**
     * Sister companies that are listed on the site (the parent excluded).
     *
     * @return list<array<string, mixed>>
     */
    public static function visibleSisterCompanies(): array
    {
        return collect(config('group.companies'))
            ->filter(fn (array $company): bool => $company['show_on_website'] ?? true)
            ->values()
            ->all();
    }

    /**
     * A file under public/site with a cache-busting version, e.g. /site/css/style.css?v=1760450583.
     *
     * The host's CDN serves CSS/JS with a long Cache-Control, so the version (the file's
     * modification time) makes a deploy reach visitors immediately.
     */
    public static function asset(string $path): string
    {
        $path = 'site/'.ltrim($path, '/');
        $mtime = @filemtime(public_path($path));

        return asset($path).($mtime ? '?v='.$mtime : '');
    }

    /**
     * Staff-authored post HTML from the Filament rich editor, reduced to a known tag set with
     * event handlers and javascript: URLs stripped, so a careless or compromised admin account
     * cannot turn a news post into stored XSS on the public site.
     */
    public static function richText(?string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><s><ul><ol><li><h1><h2><h3><h4>'
            .'<blockquote><a><img><hr><code><pre><table><thead><tbody><tr><th><td>';

        $clean = strip_tags((string) $html, $allowed);
        $clean = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

        return preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*/i', '$1=$2#', $clean);
    }
}
