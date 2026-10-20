@if ($notFound)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
        <x-storefront.ui.empty-state
            icon="gavel"
            title="Auction not found"
            description="This auction may have been withdrawn, or the link is incorrect."
        >
            <x-slot:action>
                <x-storefront.ui.button href="{{ route('storefront.auctions') }}" variant="primary">See all auctions</x-storefront.ui.button>
            </x-slot:action>
        </x-storefront.ui.empty-state>
    </div>
@else
    @php
        $state = $auction['state'];
        $isLive = $state === 'live';
        $hasBids = ($auction['bids_count'] ?? 0) > 0;
        $price = $auction['current_price_ghs'] ?? $auction['starting_price_ghs'];
        $minimum = (float) $auction['next_minimum_bid_ghs'];
        $increment = (float) $auction['bid_increment_ghs'];
        $images = $auction['images'] ?? [];
        $viewer = $auction['viewer'] ?? null;
        $isWinner = $viewer['is_winner'] ?? false;
        $isLeading = $viewer['is_leading'] ?? false;
    @endphp

    {{-- Poll while bidding is open, and after the clock stops until the result is settled. --}}
    <div class="bg-surface-muted pb-14" @if ($isLive || $state === 'upcoming' || ($state === 'ended' && empty($auction['outcome']))) wire:poll.10s="refresh" @endif>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
            <x-storefront.ui.breadcrumb :items="[
                ['label' => 'Auctions', 'href' => route('storefront.auctions')],
                ['label' => $auction['title']],
            ]" />

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_380px] items-start">
                {{-- Gallery + details --}}
                <div class="space-y-4 min-w-0">
                    <section class="bg-surface border border-border rounded-lg p-4 sm:p-6" x-data="{ active: 0 }">
                        <div class="relative aspect-[4/3] bg-surface-muted rounded-lg overflow-hidden mb-3">
                            @forelse ($images as $index => $url)
                                <img
                                    x-show="active === {{ $index }}"
                                    @if ($index > 0) x-cloak @endif
                                    src="{{ $url }}"
                                    alt="{{ $auction['title'] }}"
                                    class="w-full h-full object-contain"
                                    onerror="if (! this.src.endsWith('/images/product-placeholder.png')) this.src='{{ asset('images/product-placeholder.png') }}';"
                                >
                            @empty
                                <img src="{{ asset('images/product-placeholder.png') }}" alt="{{ $auction['title'] }}" class="w-full h-full object-cover">
                            @endforelse
                        </div>
                        @if (count($images) > 1)
                            <div class="grid grid-cols-5 sm:grid-cols-8 gap-2">
                                @foreach ($images as $index => $url)
                                    <button
                                        type="button"
                                        @click="active = {{ $index }}"
                                        class="aspect-square rounded-sm overflow-hidden border-2 transition-colors"
                                        :class="active === {{ $index }} ? 'border-accent' : 'border-border hover:border-navy-200'"
                                        aria-label="Show photo {{ $index + 1 }}"
                                    >
                                        <img src="{{ $url }}" alt="" class="w-full h-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    @if (! empty($auction['description']))
                        <section class="bg-surface border border-border rounded-lg">
                            <h2 class="px-4 sm:px-6 h-12 flex items-center border-b border-border font-display font-semibold text-lg text-navy-900">About this lot</h2>
                            <div class="px-4 sm:px-6 py-5 text-sm text-muted leading-relaxed max-w-[75ch]">{!! nl2br(e($auction['description'])) !!}</div>
                            <dl class="px-4 sm:px-6 pb-5 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                                @if (! empty($auction['condition']))
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-muted">Condition</dt>
                                        <dd class="font-medium text-fg">{{ $auction['condition'] }}</dd>
                                    </div>
                                @endif
                                @if (! empty($auction['category']['name']))
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-muted">Category</dt>
                                        <dd class="font-medium text-fg">{{ $auction['category']['name'] }}</dd>
                                    </div>
                                @endif
                                @if (! empty($auction['vendor']['business_name']))
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-muted">Sold by</dt>
                                        <dd class="font-medium"><a href="{{ route('storefront.vendor', $auction['vendor']['slug']) }}" class="text-navy-500 hover:text-accent">{{ $auction['vendor']['business_name'] }}</a></dd>
                                    </div>
                                @endif
                            </dl>
                        </section>
                    @endif

                    {{-- Bid history --}}
                    <section class="bg-surface border border-border rounded-lg">
                        <h2 class="px-4 sm:px-6 h-12 flex items-center justify-between border-b border-border font-display font-semibold text-lg text-navy-900">
                            Bid history
                            <span class="text-sm font-sans font-normal text-muted tabular-nums">{{ $auction['bids_count'] }} {{ Str::plural('bid', $auction['bids_count']) }}</span>
                        </h2>
                        @if (count($bids))
                            <ol class="divide-y divide-border">
                                @foreach ($bids as $bid)
                                    <li @class([
                                        'flex items-center justify-between gap-4 px-4 sm:px-6 py-3 text-sm',
                                        'bg-accent-soft/40' => $loop->first,
                                    ])>
                                        <span class="flex items-center gap-2 min-w-0">
                                            <span class="font-medium text-fg">{{ $bid['bidder'] }}</span>
                                            @if ($bid['is_mine'])
                                                <span class="rounded-sm bg-navy-900 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">You</span>
                                            @endif
                                            @if ($loop->first)
                                                <span class="rounded-sm bg-success/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-success">{{ $state === 'ended' ? 'Final' : 'Highest' }}</span>
                                            @endif
                                        </span>
                                        <span class="flex items-center gap-4 shrink-0">
                                            <span class="text-xs text-muted hidden sm:inline">{{ \Illuminate\Support\Carbon::parse($bid['placed_at'])->diffForHumans() }}</span>
                                            <span class="font-display font-bold text-navy-900 tabular-nums">GHS {{ number_format($bid['amount_ghs'], 2) }}</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="px-4 sm:px-6 py-5 text-sm text-muted">No bids yet. {{ $isLive ? 'Be the first — bidding starts at GHS '.number_format($auction['starting_price_ghs'], 2).'.' : '' }}</p>
                        @endif
                    </section>
                </div>

                {{-- Bid panel --}}
                <aside class="space-y-4 lg:sticky lg:top-32">
                    <section class="bg-surface border border-border rounded-lg overflow-hidden">
                        <div @class([
                            'flex items-center justify-between gap-3 px-4 h-11 text-sm font-semibold',
                            'bg-accent text-white' => $isLive,
                            'bg-navy-900 text-white' => $state === 'upcoming',
                            'bg-surface-muted text-muted border-b border-border' => ! in_array($state, ['live', 'upcoming'], true),
                        ])>
                            <span class="flex items-center gap-2">
                                @if ($isLive)
                                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span> Live auction
                                @elseif ($state === 'upcoming')
                                    <x-storefront.icon name="clock" class="w-4 h-4" /> Opens soon
                                @else
                                    <x-storefront.icon name="gavel" class="w-4 h-4" /> Auction ended
                                @endif
                            </span>
                            @if (in_array($state, ['live', 'upcoming'], true))
                                <span
                                    wire:key="countdown-{{ $state }}-{{ $auction['ends_at'] }}"
                                    x-data="countdown(@js($state === 'upcoming' ? $auction['starts_at'] : $auction['ends_at']))"
                                    class="tabular-nums"
                                    x-init="$watch('done', (isDone) => isDone && $wire.refresh())"
                                >
                                    <span x-text="done ? 'Updating…' : label"></span>
                                </span>
                            @endif
                        </div>

                        <div class="p-4">
                            <h1 class="font-display font-bold text-xl leading-snug text-navy-900 mb-4 text-balance">{{ $auction['title'] }}</h1>

                            <p class="text-xs uppercase tracking-wide text-muted">
                                {{ $state === 'ended' ? (($auction['outcome'] ?? null) === 'sold' ? 'Winning bid' : 'Highest bid') : ($hasBids ? 'Current bid' : 'Starting bid') }}
                            </p>
                            <p class="font-display font-bold text-3xl text-navy-900 tabular-nums">GHS {{ number_format($price, 2) }}</p>

                            <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div class="rounded-sm bg-surface-muted px-3 py-2">
                                    <dt class="text-muted">Bids</dt>
                                    <dd class="font-semibold text-fg tabular-nums">{{ $auction['bids_count'] }}</dd>
                                </div>
                                <div class="rounded-sm bg-surface-muted px-3 py-2">
                                    <dt class="text-muted">Reserve</dt>
                                    <dd class="font-semibold {{ $auction['has_reserve'] && ! $auction['reserve_met'] ? 'text-warning' : 'text-success' }}">
                                        {{ ! $auction['has_reserve'] ? 'No reserve' : ($auction['reserve_met'] ? 'Reserve met' : 'Not yet met') }}
                                    </dd>
                                </div>
                                <div class="col-span-2 rounded-sm bg-surface-muted px-3 py-2">
                                    <dt class="text-muted">{{ $state === 'upcoming' ? 'Opens' : ($state === 'ended' ? 'Closed' : 'Closes') }}</dt>
                                    <dd class="font-semibold text-fg">
                                        {{ \Illuminate\Support\Carbon::parse($state === 'upcoming' ? $auction['starts_at'] : ($auction['closed_at'] ?? $auction['ends_at']))->timezone(config('app.timezone'))->format('D j M Y, g:i a') }}
                                    </dd>
                                </div>
                            </dl>

                            {{-- Viewer status --}}
                            @if ($isWinner)
                                <div class="mt-4 rounded-sm border border-success/30 bg-success/10 p-3 text-sm">
                                    @if ($viewer['is_paid'] ?? false)
                                        <p class="font-semibold text-success">You won — payment received. Thank you!</p>
                                        <a href="{{ route('storefront.account.orders') }}" class="mt-1 inline-block font-semibold text-navy-500 hover:text-accent">Track it in My Orders</a>
                                    @elseif ($viewer['is_second_chance'] ?? false)
                                        <p class="font-semibold text-navy-900">Second-chance offer</p>
                                        <p class="text-muted mt-0.5">The winning bidder did not pay, so you can buy this item at your highest bid of GHS {{ number_format($auction['current_price_ghs'] ?? 0, 2) }}. It's optional.</p>
                                        @if (! empty($viewer['payment_due_at']))
                                            <p class="text-muted mt-0.5">Offer open until {{ \Illuminate\Support\Carbon::parse($viewer['payment_due_at'])->timezone(config('app.timezone'))->format('D j M, g:i a') }}.</p>
                                        @endif
                                        <div class="mt-3 grid grid-cols-2 gap-2">
                                            <x-storefront.ui.button wire:click="declineOffer" wire:confirm="Decline this offer? It will go to the next bidder." variant="secondary" class="w-full">No thanks</x-storefront.ui.button>
                                            <x-storefront.ui.button href="{{ route('storefront.auction.pay', $auction['slug']) }}" variant="accent" class="w-full">
                                                <x-storefront.icon name="lock" class="w-4 h-4" /> Buy now
                                            </x-storefront.ui.button>
                                        </div>
                                    @else
                                        <p class="font-semibold text-success">Congratulations — you won this auction.</p>
                                        @if (! empty($viewer['payment_due_at']))
                                            <p class="text-muted mt-0.5">Please pay by {{ \Illuminate\Support\Carbon::parse($viewer['payment_due_at'])->timezone(config('app.timezone'))->format('D j M, g:i a') }}.</p>
                                        @endif
                                        <x-storefront.ui.button href="{{ route('storefront.auction.pay', $auction['slug']) }}" variant="accent" class="mt-3 w-full">
                                            <x-storefront.icon name="lock" class="w-4 h-4" /> Pay now
                                        </x-storefront.ui.button>
                                    @endif
                                </div>
                            @elseif ($isLive && $isLeading)
                                <p class="mt-4 flex items-center gap-2 rounded-sm bg-success/10 px-3 py-2 text-sm font-semibold text-success">
                                    <x-storefront.icon name="check-circle" class="w-4 h-4" /> You're the highest bidder
                                </p>
                            @elseif ($isLive && ($viewer['has_bid'] ?? false))
                                <p class="mt-4 flex items-center gap-2 rounded-sm bg-warning/10 px-3 py-2 text-sm font-semibold text-warning">
                                    <x-storefront.icon name="exclamation-circle" class="w-4 h-4" /> You've been outbid
                                </p>
                            @elseif ($state === 'ended' && ($viewer['has_bid'] ?? false))
                                <p class="mt-4 rounded-sm bg-surface-muted px-3 py-2 text-sm text-muted">This auction ended without your bid winning.</p>
                            @elseif ($state === 'ended')
                                <p class="mt-4 rounded-sm bg-surface-muted px-3 py-2 text-sm text-muted">
                                    {{ match ($auction['outcome'] ?? null) {
                                        'sold' => 'This lot has been sold.',
                                        'reserve_not_met' => 'Bidding closed below the reserve, so this lot did not sell.',
                                        'no_bids' => 'This auction closed without bids.',
                                        'unpaid' => 'This auction closed without a completed sale.',
                                        default => 'Bidding has closed.',
                                    } }}
                                </p>
                            @endif

                            {{-- Bid form --}}
                            @if ($isLive && ! $isLeading)
                                @auth
                                    <form
                                        wire:submit="placeBid"
                                        x-data="{ confirming: false }"
                                        @toast.window="confirming = false"
                                        class="mt-4 space-y-3"
                                    >
                                        <div>
                                            <label for="bid-amount" class="block text-sm font-medium text-fg mb-1.5">Your bid (GHS)</label>
                                            <div class="flex">
                                                <span class="inline-flex items-center px-3 rounded-l-sm border border-r-0 border-border bg-surface-muted text-sm text-muted">GHS</span>
                                                <input
                                                    id="bid-amount"
                                                    type="number"
                                                    inputmode="decimal"
                                                    step="0.01"
                                                    min="{{ $minimum }}"
                                                    wire:model="bidAmount"
                                                    @input="confirming = false"
                                                    class="flex-1 min-w-0 border border-border rounded-r-sm px-3 h-11 text-base font-semibold tabular-nums focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"
                                                >
                                            </div>
                                            <p class="mt-1 text-xs text-muted">Minimum bid GHS {{ number_format($minimum, 2) }}</p>
                                            @error('bidAmount') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                                            @if ($bidError) <p class="mt-1 text-xs text-error">{{ $bidError }}</p> @endif
                                        </div>

                                        <div class="grid grid-cols-3 gap-2">
                                            @foreach ([0, 1, 4] as $steps)
                                                @php $quick = $minimum + $steps * $increment; @endphp
                                                <button
                                                    type="button"
                                                    wire:click="setQuickBid({{ $quick }})"
                                                    @click="confirming = false"
                                                    class="h-9 rounded-sm border border-border text-xs font-semibold text-navy-900 hover:border-navy-900 tabular-nums"
                                                >
                                                    {{ number_format($quick, 0) }}
                                                </button>
                                            @endforeach
                                        </div>

                                        <button
                                            type="button"
                                            x-show="! confirming"
                                            @click="confirming = true"
                                            class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-sm bg-accent text-white font-semibold hover:bg-accent-hover"
                                        >
                                            <x-storefront.icon name="gavel" class="w-5 h-5" /> Place bid
                                        </button>

                                        <div x-show="confirming" x-cloak class="rounded-sm border border-accent/30 bg-accent-soft p-3 space-y-3">
                                            <p class="text-sm text-fg">
                                                Confirm your bid of <strong class="tabular-nums">GHS <span x-text="Number($wire.bidAmount || 0).toFixed(2)"></span></strong>.
                                                If you win, you agree to buy this item at that price.
                                            </p>
                                            <div class="flex gap-2">
                                                <button type="button" @click="confirming = false" class="flex-1 h-10 rounded-sm border border-border bg-surface text-sm font-semibold text-fg">Cancel</button>
                                                <button
                                                    type="submit"
                                                    wire:loading.attr="disabled"
                                                    wire:target="placeBid"
                                                    class="flex-1 h-10 inline-flex items-center justify-center gap-2 rounded-sm bg-accent text-sm font-semibold text-white hover:bg-accent-hover disabled:opacity-60"
                                                >
                                                    <x-storefront.ui.spinner class="w-4 h-4" wire:loading wire:target="placeBid" />
                                                    Confirm bid
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    <x-storefront.ui.button href="{{ route('storefront.login') }}" variant="accent" size="lg" class="mt-4 w-full">
                                        Sign in to bid
                                    </x-storefront.ui.button>
                                    <p class="mt-2 text-xs text-muted text-center">New here? <a href="{{ route('storefront.register') }}" class="font-semibold text-navy-500 hover:text-accent">Create an account</a></p>
                                @endauth
                            @elseif ($state === 'upcoming')
                                <p class="mt-4 rounded-sm bg-navy-900/5 px-3 py-2 text-sm text-fg">Bidding opens at GHS {{ number_format($auction['starting_price_ghs'], 2) }}. Come back when the countdown ends.</p>
                            @endif
                        </div>
                    </section>

                    <ul class="bg-surface border border-border rounded-lg divide-y divide-border text-sm">
                        <li class="flex items-start gap-3 p-4">
                            <x-storefront.icon name="clock" class="w-5 h-5 text-navy-500 shrink-0" />
                            <span class="text-muted">
                                <span class="font-semibold text-fg">Fair finish.</span>
                                @if (($auction['extension_minutes'] ?? 0) > 0)
                                    A bid in the last {{ $auction['extension_minutes'] }} {{ Str::plural('minute', $auction['extension_minutes']) }} extends the auction by {{ $auction['extension_minutes'] }} {{ Str::plural('minute', $auction['extension_minutes']) }}.
                                @else
                                    The auction closes exactly at the time shown.
                                @endif
                            </span>
                        </li>
                        <li class="flex items-start gap-3 p-4">
                            <x-storefront.icon name="lock" class="w-5 h-5 text-navy-500 shrink-0" />
                            <span class="text-muted"><span class="font-semibold text-fg">Secure payment.</span> The winner pays through checkout — Mobile Money or card.</span>
                        </li>
                        <li class="flex items-start gap-3 p-4">
                            <x-storefront.icon name="shield-check" class="w-5 h-5 text-navy-500 shrink-0" />
                            <span class="text-muted"><span class="font-semibold text-fg">Bids are binding.</span> Read the <a href="{{ route('storefront.terms') }}#auctions" class="text-navy-500 hover:text-accent">auction terms</a> before you bid.</span>
                        </li>
                    </ul>
                </aside>
            </div>
        </div>
    </div>
@endif
