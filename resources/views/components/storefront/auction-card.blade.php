@props(['auction'])

@php
    $state = $auction['state'] ?? 'live';
    $price = $auction['current_price_ghs'] ?? $auction['starting_price_ghs'] ?? 0;
    $hasBids = ($auction['bids_count'] ?? 0) > 0;
    $image = $auction['images'][0] ?? null;
    $viewer = $auction['viewer'] ?? null;
    $countdownTo = $state === 'upcoming' ? ($auction['starts_at'] ?? null) : ($auction['ends_at'] ?? null);
@endphp

<a
    href="{{ route('storefront.auction', $auction['slug']) }}"
    class="group flex flex-col bg-surface border border-border rounded-lg overflow-hidden hover:border-navy-200 hover:shadow-float transition duration-150"
    wire:key="auction-{{ $auction['id'] }}"
>
    <div class="relative aspect-square bg-surface-muted overflow-hidden">
        <img
            src="{{ $image ?? asset('images/product-placeholder.png') }}"
            alt="{{ $auction['title'] }}"
            loading="lazy"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 {{ $state === 'ended' ? 'grayscale-[40%]' : '' }}"
            onerror="if (! this.src.endsWith('/images/product-placeholder.png')) this.src='{{ asset('images/product-placeholder.png') }}';"
        >

        <span @class([
            'absolute top-2 left-2 inline-flex items-center gap-1 rounded-sm px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide',
            'bg-accent text-white' => $state === 'live',
            'bg-navy-900 text-white' => $state === 'upcoming',
            'bg-surface text-muted border border-border' => in_array($state, ['ended', 'cancelled'], true),
        ])>
            @if ($state === 'live')
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Live
            @elseif ($state === 'upcoming')
                Opens soon
            @else
                Ended
            @endif
        </span>

        @if ($viewer && ($viewer['is_winner'] ?? false))
            <span class="absolute top-2 right-2 rounded-sm bg-success px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">You won</span>
        @elseif ($viewer && ($viewer['is_leading'] ?? false) && $state === 'live')
            <span class="absolute top-2 right-2 rounded-sm bg-success px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">Leading</span>
        @elseif ($viewer && ($viewer['has_bid'] ?? false) && $state === 'live')
            <span class="absolute top-2 right-2 rounded-sm bg-warning px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">Outbid</span>
        @endif

        @if ($countdownTo && in_array($state, ['live', 'upcoming'], true))
            <div
                x-data="countdown(@js($countdownTo))"
                class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-navy-950/80 px-3 py-1.5 text-xs text-white"
            >
                <span class="flex items-center gap-1.5">
                    <x-storefront.icon name="clock" class="w-3.5 h-3.5" />
                    {{ $state === 'upcoming' ? 'Opens in' : 'Ends in' }}
                </span>
                <span class="font-semibold tabular-nums" :class="urgent && 'text-accent'" x-text="done ? 'Now' : label"></span>
            </div>
        @endif
    </div>

    <div class="flex flex-col flex-1 p-3">
        <h3 class="text-sm text-fg leading-snug line-clamp-2 min-h-10 group-hover:text-accent">{{ $auction['title'] }}</h3>

        <div class="mt-2 flex items-end justify-between gap-2">
            <div class="leading-tight">
                <p class="text-[11px] uppercase tracking-wide text-muted">
                    {{ $state === 'ended' ? (($auction['outcome'] ?? null) === 'sold' ? 'Sold for' : 'Final bid') : ($hasBids ? 'Current bid' : 'Starting bid') }}
                </p>
                <p class="font-display font-bold text-base text-navy-900 tabular-nums">GHS {{ number_format($price, 2) }}</p>
            </div>
            <p class="text-xs text-muted tabular-nums shrink-0">
                {{ $auction['bids_count'] ?? 0 }} {{ \Illuminate\Support\Str::plural('bid', $auction['bids_count'] ?? 0) }}
            </p>
        </div>
    </div>
</a>
