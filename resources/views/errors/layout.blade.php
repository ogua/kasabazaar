{{-- Error pages deliberately skip the storefront layout: no API call for the
     header categories, no Livewire, no session reads — any of which may be the
     thing that failed. Brand, a plain message and a way back are enough. --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>

<body class="bg-surface-muted text-fg font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="bg-surface border-b border-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center">
                <a href="{{ url('/') }}" aria-label="{{ config('app.name') }} home">
                    <img src="{{ asset('images/brand/logo-lockup.png') }}" alt="{{ config('app.name') }}" class="h-7 w-auto" width="1200" height="160">
                </a>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-4 py-16">
            <div class="max-w-md text-center">
                <p class="font-display font-bold text-6xl text-navy-200 tabular-nums mb-2">@yield('code')</p>
                <h1 class="font-display font-bold text-2xl text-navy-900 mb-3 wake-underline">@yield('heading')</h1>
                <p class="text-muted mt-4 mb-8">@yield('message')</p>
                <div class="flex flex-wrap justify-center gap-3">
                    @yield('actions')
                </div>
            </div>
        </main>

        <footer class="py-6 text-center text-xs text-muted">
            Need help? <a href="mailto:{{ config('group.contact.email') }}" class="text-navy-500 hover:text-accent">{{ config('group.contact.email') }}</a>
            · <a href="tel:{{ config('group.contact.phone_gh_tel') }}" class="text-navy-500 hover:text-accent tabular-nums">{{ config('group.contact.phone_gh') }}</a>
        </footer>
    </div>
</body>

</html>
