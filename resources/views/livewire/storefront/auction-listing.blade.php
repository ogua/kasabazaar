<div class="bg-surface-muted pb-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
        <x-storefront.ui.breadcrumb :items="[['label' => 'Auctions']]" />

        {{-- Intro + how it works --}}
        <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_420px] mb-4">
            <div class="relative overflow-hidden rounded-lg bg-navy-900 text-white px-6 sm:px-10 py-8">
                <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full border-[24px] border-white/5" aria-hidden="true"></div>
                <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-white/80 mb-3">
                    <span class="w-6 h-0.5 bg-accent"></span> {{ config('app.name') }} Auctions
                </p>
                <h1 class="font-display font-bold text-3xl sm:text-4xl leading-tight mb-3 text-balance">Bid on one-of-a-kind finds.</h1>
                <p class="text-white/75 max-w-xl">
                    Every lot is listed by a vetted vendor. Bid what it is worth to you — the highest bid when the clock
                    runs out wins, and pays through our secure checkout.
                </p>
            </div>

            <ol class="bg-surface border border-border rounded-lg divide-y divide-border text-sm">
                @foreach ([
                    ['icon' => 'gavel', 'title' => 'Place your bid', 'desc' => 'Bid at least the minimum shown. Every bid is a commitment to buy.'],
                    ['icon' => 'clock', 'title' => 'Watch the clock', 'desc' => 'A late bid adds a couple of minutes, so nobody wins by sniping.'],
                    ['icon' => 'lock', 'title' => 'Win & pay securely', 'desc' => 'The winner pays within '.config('group.auctions.payment_window_hours', 48).' hours through checkout.'],
                ] as $step)
                    <li class="flex items-start gap-3 px-4 py-3.5">
                        <span class="flex items-center justify-center w-9 h-9 rounded-full bg-accent-soft text-accent shrink-0">
                            <x-storefront.icon :name="$step['icon']" class="w-4 h-4" />
                        </span>
                        <span class="leading-snug">
                            <span class="block font-semibold text-fg">{{ $step['title'] }}</span>
                            <span class="block text-muted text-xs mt-0.5">{{ $step['desc'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Toolbar --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-surface border border-border rounded-lg px-3 py-2 mb-3">
            <div class="flex gap-1 overflow-x-auto -mx-1 px-1" role="tablist" aria-label="Auction status">
                @foreach ($states as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        aria-selected="{{ $state === $key ? 'true' : 'false' }}"
                        wire:click="setState('{{ $key }}')"
                        @class([
                            'shrink-0 rounded-sm px-3 h-9 text-sm font-semibold whitespace-nowrap transition-colors',
                            'bg-navy-900 text-white' => $state === $key,
                            'text-muted hover:text-fg hover:bg-surface-muted' => $state !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="relative md:w-72">
                <label for="auction-search" class="sr-only">Search auctions</label>
                <x-storefront.icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                <input
                    id="auction-search"
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search auctions"
                    class="w-full border border-border rounded-sm pl-9 pr-3 h-9 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"
                >
            </div>
        </div>

        @if ($error)
            <x-storefront.ui.alert variant="error">{{ $error }}</x-storefront.ui.alert>
        @endif

        <div
            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 transition-opacity"
            wire:loading.class="opacity-40"
            wire:target="setState,search,gotoPage,previousPage,nextPage"
        >
            @forelse ($auctions as $auction)
                <x-storefront.auction-card :auction="$auction" />
            @empty
                @unless ($error)
                    <div class="col-span-full">
                        <x-storefront.ui.empty-state
                            icon="gavel"
                            :title="$state === 'ended' ? 'No finished auctions yet' : 'No auctions running right now'"
                            description="New lots are added regularly — check back soon, or browse the shop in the meantime."
                            class="bg-surface"
                        >
                            <x-slot:action>
                                <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="primary">Browse the shop</x-storefront.ui.button>
                            </x-slot:action>
                        </x-storefront.ui.empty-state>
                    </div>
                @endunless
            @endforelse
        </div>

        <x-storefront.ui.pagination :meta="$meta" />
    </div>
</div>
