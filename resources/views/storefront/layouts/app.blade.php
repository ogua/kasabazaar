<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', config('app.name').' — a multi-vendor marketplace in Ghana.')">

    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', config('app.name').' — shop from local vendors across Ghana.')">
    <meta property="og:image" content="@yield('og_image', asset('images/brand/og-image.png'))">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#0F2247">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles
</head>

<body class="bg-bg text-fg font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        @include('storefront.partials.header')

        <main class="flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        @include('storefront.partials.footer')
    </div>

    @livewire('storefront.shared.toast')

    @if ($whatsapp = config('services.whatsapp.number'))
        <a
            href="https://wa.me/{{ $whatsapp }}"
            target="_blank"
            rel="noopener"
            class="fixed bottom-6 right-6 z-40 flex items-center justify-center w-14 h-14 rounded-full bg-[#25D366] text-white shadow-float hover:scale-105 transition-transform duration-150"
            aria-label="Chat with us on WhatsApp"
        >
            <x-storefront.icon name="whatsapp" class="w-7 h-7" />
        </a>
    @endif

    <button
        x-data="{ visible: false }"
        x-init="window.addEventListener('scroll', () => visible = window.scrollY > 480)"
        x-show="visible"
        x-transition.opacity
        x-cloak
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-6 left-6 z-40 flex items-center justify-center w-11 h-11 rounded-full bg-navy-900 text-white shadow-float hover:bg-navy-700"
        aria-label="Scroll to top"
    >
        <x-storefront.icon name="chevron-right" class="w-5 h-5 -rotate-90" />
    </button>

    @livewireScripts
    @stack('scripts')
</body>

</html>
