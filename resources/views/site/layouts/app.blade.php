{{-- Section values arrive already escaped (inline @section escapes its value), so they are printed raw below. --}}
@php
    $siteName = config('group.parent.name');
    $pageTitle = trim($__env->yieldContent('title')) ?: $siteName;
    $metaDescription = trim($__env->yieldContent('meta_description')) ?: 'KASAROSE LOGISTICS — door-to-door air and sea freight between the US and Ghana, ecommerce package forwarding, shipment tracking, real estate and property management.';
    $metaRobots = trim($__env->yieldContent('meta_robots')) ?: 'index, follow';
    $canonicalUrl = url()->current();
    $orgLogo = asset('site/images/kasarose-logistics-logo.png');
    $ogImage = trim($__env->yieldContent('og_image')) ?: $orgLogo;
    $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
    $fullTitle = $pageTitle.' | '.$siteName;

    $breadcrumbJson = null;
    if ($metaRobots === 'index, follow' && ! request()->routeIs('home-page')) {
        $breadcrumbItems = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home-page')]];
        if (request()->routeIs('blog-details')) {
            $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'News', 'item' => route('our-blog')];
        }
        $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => count($breadcrumbItems) + 1, 'name' => html_entity_decode($pageTitle, ENT_QUOTES), 'item' => $canonicalUrl];
        $breadcrumbJson = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }

    $organizationJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => route('home-page'),
        'logo' => $orgLogo,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => config('website.contact.address_gh'),
            'addressCountry' => 'GH',
        ],
        'contactPoint' => [
            ['@type' => 'ContactPoint', 'telephone' => config('website.contact.phone_us_1_tel'), 'contactType' => 'customer service', 'areaServed' => 'US'],
            ['@type' => 'ContactPoint', 'telephone' => config('website.contact.phone_gh_1_tel'), 'contactType' => 'customer service', 'areaServed' => 'GH'],
        ],
        'email' => config('website.contact.email_support'),
        'subOrganization' => collect($groupCompanies)->where('external', true)->map(fn (array $company): array => [
            '@type' => 'OnlineStore',
            'name' => $company['name'],
            'url' => $company['url'],
        ])->values()->all(),
        'sameAs' => collect($groupCompanies)->where('external', true)->pluck('url')->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="utf-8">
   <meta http-equiv="x-ua-compatible" content="ie=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <meta name="csrf-token" content="{{ csrf_token() }}">
   <meta name="description" content="{!! $metaDescription !!}">
   <meta name="robots" content="{!! $metaRobots !!}">
   <meta name="author" content="{{ $siteName }}">
   <meta name="theme-color" content="#052240">
   <link rel="canonical" href="{{ $canonicalUrl }}">
   <title>{!! $fullTitle !!}</title>

   <!-- Open Graph / Facebook -->
   <meta property="og:type" content="{!! $ogType !!}">
   <meta property="og:site_name" content="{{ $siteName }}">
   <meta property="og:url" content="{{ $canonicalUrl }}">
   <meta property="og:title" content="{!! $fullTitle !!}">
   <meta property="og:description" content="{!! $metaDescription !!}">
   <meta property="og:image" content="{!! $ogImage !!}">

   <!-- Twitter -->
   <meta name="twitter:card" content="summary">
   <meta name="twitter:title" content="{!! $fullTitle !!}">
   <meta name="twitter:description" content="{!! $metaDescription !!}">
   <meta name="twitter:image" content="{!! $ogImage !!}">

   <!-- Favicons -->
   <link rel="icon" type="image/x-icon" href="{{ asset('site/images/favicon.ico') }}">
   <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('site/images/favicon-32x32.png') }}">
   <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('site/images/favicon-16x16.png') }}">
   <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('site/images/apple-touch-icon.png') }}">
   <link rel="manifest" href="{{ asset('site/images/site.webmanifest') }}">
   <meta name="apple-itunes-app" content="app-id={{ config('website.mobile_app.ios_app_id') }}">

   <script type="application/ld+json">{!! $organizationJson !!}</script>
   @if ($breadcrumbJson !== null)
   <script type="application/ld+json">{!! $breadcrumbJson !!}</script>
   @endif
   @stack('structured-data')

   <!-- Google Fonts -->
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap"
      rel="stylesheet">
   <!-- CSS Files -->
   <link rel="stylesheet" href="{{ asset('site/css/bootstrap.min.css') }}">
   <link rel="stylesheet" href="{{ asset('site/css/fancybox.css') }}">
   <link rel="stylesheet" href="{{ asset('site/css/swiper-bundle.min.css') }}">
   <link rel="stylesheet" href="{{ asset('site/css/all.min.css') }}">
   <link rel="stylesheet" href="{{ asset('site/css/jarallax.min.css') }}">
   <link rel="stylesheet" href="{{ asset('site/css/nice-select.css') }}">
   <link rel="stylesheet" href="{{ asset('site/css/jquery.datepicker.css') }}">
   <link rel="stylesheet" href="{{ asset('site/css/jquery.timepicker.min.css') }}">
   <!-- Style CSS -->
   <link rel="stylesheet" href="@siteAsset('css/style.css')">
   @stack('styles')
</head>

<body>
   <div class="main-overlay"></div>
   <!-- Preloader -->
   <div id="preloader">
      <div class="preloader">
         <span></span>
         <span></span>
      </div>
   </div>

@include('site.partials.header')

@yield('content')

@include('site.partials.footer')

   <!--Javascript
========================================================-->
   <script src="{{ asset('site/js/jquery-3.7.1.min.js') }}"></script>
   <script src="{{ asset('site/js/bootstrap.bundle.min.js') }}"></script>
   <script src="{{ asset('site/js/swiper-bundle.min.js') }}"></script>
   <script src="{{ asset('site/js/lenis.min.js') }}"></script>
   <script src="{{ asset('site/js/jarallax.min.js') }}"></script>
   <script src="{{ asset('site/js/gsap.min.js') }}"></script>
   <script src="{{ asset('site/js/ScrollTrigger.min.js') }}"></script>
   <script src="{{ asset('site/js/SplitText.js') }}"></script>
   <script src="{{ asset('site/js/fancybox.umd.js') }}"></script>
   <script src="{{ asset('site/js/isotope.pkgd.min.js') }}"></script>
   <script src="{{ asset('site/js/purecounter.js') }}"></script>
   <script src="{{ asset('site/js/jquery.marquee.min.js') }}"></script>
   <script src="{{ asset('site/js/jquery.nice-select.min.js') }}"></script>
   <script src="{{ asset('site/js/jquery.timepicker.min.js') }}"></script>
   <script src="{{ asset('site/js/bootstrap-datepicker.js') }}"></script>
   <script src="@siteAsset('js/form.js')"></script>
   <script src="@siteAsset('js/custom.js')"></script>
   @stack('scripts')
</body>

</html>
