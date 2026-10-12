<x-storefront.account-layout title="My Auctions">
    <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Auctions you've bid on</h2>

    @if ($error)
        <x-storefront.ui.alert variant="error">{{ $error }}</x-storefront.ui.alert>
    @elseif (empty($auctions))
        <x-storefront.ui.empty-state icon="gavel" title="No bids yet" description="When you bid on an auction it shows up here, with whether you're winning.">
            <x-slot:action>
                <x-storefront.ui.button href="{{ route('storefront.auctions') }}" variant="primary">Browse auctions</x-storefront.ui.button>
            </x-slot:action>
        </x-storefront.ui.empty-state>
    @else
        <ul class="border border-border rounded-lg divide-y divide-border bg-surface" wire:loading.class="opacity-50" wire:target="gotoPage,previousPage,nextPage">
            @foreach ($auctions as $auction)
                @php
                    $viewer = $auction['viewer'] ?? [];
                    [$label, $classes] = match (true) {
                        ($viewer['is_winner'] ?? false) && ($viewer['is_paid'] ?? false) => ['Won · paid', 'bg-success/10 text-success'],
                        $viewer['is_winner'] ?? false => ['Won · payment due', 'bg-accent-soft text-accent-hover'],
                        $auction['state'] === 'live' && ($viewer['is_leading'] ?? false) => ['Leading', 'bg-success/10 text-success'],
                        $auction['state'] === 'live' => ['Outbid', 'bg-warning/10 text-warning'],
                        $auction['state'] === 'ended' => ['Not won', 'bg-surface-muted text-muted'],
                        default => [ucfirst($auction['state']), 'bg-surface-muted text-muted'],
                    };
                @endphp
                <li class="flex items-center gap-4 p-4" wire:key="my-auction-{{ $auction['id'] }}">
                    <img src="{{ $auction['images'][0] ?? asset('images/product-placeholder.png') }}" alt="" class="w-16 h-16 rounded-sm object-cover border border-border shrink-0">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('storefront.auction', $auction['slug']) }}" class="text-sm font-medium text-fg hover:text-accent line-clamp-1">{{ $auction['title'] }}</a>
                        <p class="text-xs text-muted mt-0.5 tabular-nums">
                            {{ $auction['state'] === 'ended' ? 'Final' : 'Current' }} bid GHS {{ number_format($auction['current_price_ghs'] ?? $auction['starting_price_ghs'], 2) }}
                            · {{ $auction['bids_count'] }} {{ Str::plural('bid', $auction['bids_count']) }}
                        </p>
                    </div>
                    <span class="rounded-sm px-2 py-1 text-xs font-semibold whitespace-nowrap {{ $classes }}">{{ $label }}</span>
                    @if (($viewer['is_winner'] ?? false) && ! ($viewer['is_paid'] ?? false))
                        <x-storefront.ui.button href="{{ route('storefront.auction.pay', $auction['slug']) }}" variant="accent" size="sm">Pay now</x-storefront.ui.button>
                    @elseif ($auction['state'] === 'live' && ! ($viewer['is_leading'] ?? false))
                        <x-storefront.ui.button href="{{ route('storefront.auction', $auction['slug']) }}" variant="secondary" size="sm">Bid again</x-storefront.ui.button>
                    @endif
                </li>
            @endforeach
        </ul>

        <x-storefront.ui.pagination :meta="$meta" />
    @endif
</x-storefront.account-layout>
