<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', config('app.name').' — a multi-vendor marketplace in Ghana.')">

    {{-- Open Graph / social share meta (gap D.9) --}}
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', config('app.name').' — shop from local vendors across Ghana.')">
    <meta property="og:image" content="@yield('og_image', asset('storefront/assets/images/demos/demo2/header-logo.png'))">
    <meta property="og:type" content="website">

    <link rel="icon" type="image/png" href="{{ asset('storefront/assets/images/icons/favicon.png') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('storefront/assets/vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storefront/assets/vendor/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('storefront/assets/vendor/animate/animate.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('storefront/assets/vendor/magnific-popup/magnific-popup.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('storefront/assets/css/demo2.min.css') }}">

    @stack('styles')
    @livewireStyles
</head>

<body class="@yield('body_class', 'home')">
    <div class="page-wrapper">
        @include('storefront.partials.header')

        <main class="page-content">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        @include('storefront.partials.footer')
    </div>

    @livewire('storefront.shared.toast')

    {{-- Click-to-WhatsApp floating contact button (gap D.9) --}}
    @if ($whatsapp = config('services.whatsapp.number'))
        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener"
           class="whatsapp-float"
           style="position:fixed;bottom:24px;right:24px;z-index:999;width:56px;height:56px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.25);">
            <i class="fab fa-whatsapp" style="color:#fff;font-size:28px;"></i>
        </a>
    @endif

    <a id="scroll-top" class="scroll-top" href="#top" title="Top" role="button"><i class="w-icon-angle-up"></i></a>

    <script src="{{ asset('storefront/assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('storefront/assets/vendor/jquery.plugin/jquery.plugin.min.js') }}"></script>
    <script src="{{ asset('storefront/assets/vendor/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
    <script src="{{ asset('storefront/assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('storefront/assets/vendor/jquery.countdown/jquery.countdown.min.js') }}"></script>
    <script src="{{ asset('storefront/assets/vendor/magnific-popup/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('storefront/assets/vendor/floating-parallax/parallax.min.js') }}"></script>
    <script src="{{ asset('storefront/assets/vendor/zoom/jquery.zoom.js') }}"></script>
    <script src="{{ asset('storefront/assets/js/main.min.js') }}"></script>

    @livewireScripts
    <script src="{{ asset('js/storefront-livewire-bridge.js') }}"></script>
    @stack('scripts')
</body>

</html>
