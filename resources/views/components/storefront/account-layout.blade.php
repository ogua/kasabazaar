@props(['title'])

@php
    $user = auth()->user();
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user?->name ?? 'A', 0, 1));
@endphp

<div class="bg-surface-muted pb-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
        <x-storefront.ui.breadcrumb :items="request()->routeIs('storefront.account.dashboard')
            ? [['label' => 'My Account']]
            : [['label' => 'My Account', 'href' => route('storefront.account.dashboard')], ['label' => $title]]" />

        {{-- Profile header --}}
        <section class="flex flex-wrap items-center gap-4 bg-navy-900 text-white rounded-lg px-5 sm:px-6 py-5 mb-4">
            <span class="flex items-center justify-center w-12 h-12 rounded-full bg-accent font-display font-bold text-xl shrink-0" aria-hidden="true">{{ $initial }}</span>
            <div class="min-w-0 flex-1">
                <h1 class="font-display font-bold text-xl leading-tight truncate">{{ $title }}</h1>
                <p class="text-sm text-white/70 truncate">{{ $user?->email }}</p>
            </div>
            <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="accent" size="sm" class="shrink-0">Continue shopping</x-storefront.ui.button>
        </section>

        <div class="grid gap-4 md:grid-cols-[240px_minmax(0,1fr)] items-start">
            <div class="md:sticky md:top-32">
                @include('storefront.account.nav')
            </div>
            <section class="min-w-0 bg-surface border border-border rounded-lg p-4 sm:p-6">
                {{ $slot }}
            </section>
        </div>
    </div>
</div>
