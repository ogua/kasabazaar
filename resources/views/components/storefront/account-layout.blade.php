@props(['title'])

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="font-display font-bold text-2xl text-navy-900 mb-8">{{ $title }}</h1>

    <div class="grid md:grid-cols-4 gap-8">
        <div class="md:col-span-1">
            @include('storefront.account.nav')
        </div>
        <div class="md:col-span-3">
            {{ $slot }}
        </div>
    </div>
</div>
