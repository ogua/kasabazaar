<div class="bg-surface-muted pb-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
        <x-storefront.ui.breadcrumb :items="[['label' => 'Membership']]" />

        <section class="relative overflow-hidden rounded-lg bg-navy-900 text-white px-6 sm:px-10 py-8 mb-6">
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full border-[24px] border-white/5" aria-hidden="true"></div>
            <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-white/80 mb-3">
                <span class="w-6 h-0.5 bg-accent"></span> {{ config('app.name') }} Membership
            </p>
            <h1 class="font-display font-bold text-3xl sm:text-4xl leading-tight mb-3 text-balance">Shop smarter as a member.</h1>
            <p class="text-white/75 max-w-2xl">
                Pay your membership dues once a year to unlock discounts on marketplace products, entry to exclusive
                auctions and our community of buyers on WhatsApp.
            </p>
        </section>

        @if ($error)
            <x-storefront.ui.alert variant="error" class="mb-6">{{ $error }}</x-storefront.ui.alert>
        @endif

        @if (session('membership_activated'))
            <x-storefront.ui.alert variant="success" class="mb-6">{{ session('membership_activated') }}</x-storefront.ui.alert>
        @endif

        @if ($loadError)
            <x-storefront.ui.alert variant="error">{{ $loadError }}</x-storefront.ui.alert>
        @else
            @if ($active)
                <section class="bg-surface border border-border rounded-lg p-5 sm:p-6 mb-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted mb-1">Your membership</p>
                            <h2 class="font-display font-bold text-2xl text-navy-900">{{ $active['plan']['name'] }}</h2>
                            <p class="text-sm text-muted mt-1">
                                Active until <strong class="text-fg">{{ \Illuminate\Support\Carbon::parse($active['expires_at'])->format('j F Y') }}</strong>
                                ({{ $active['days_left'] }} {{ \Illuminate\Support\Str::plural('day', $active['days_left']) }} left).
                                Renew any time and the time you have left is kept.
                            </p>
                        </div>
                        @if (! empty($active['whatsapp_group_url']))
                            <x-storefront.ui.button :href="$active['whatsapp_group_url']" target="_blank" rel="noopener" variant="accent">
                                <x-storefront.icon name="user-group" class="w-4 h-4" /> Join the buyers WhatsApp group
                            </x-storefront.ui.button>
                        @endif
                    </div>
                    @if (! empty($active['whatsapp_group_url']))
                        <p class="mt-4 text-xs text-muted">
                            The buyers group is for discussing products and sharing information between buyers. It is not a place to sell, and listings or sales offers will be removed.
                        </p>
                    @endif
                </section>
            @endif

            @php $heldTier = $active['plan']['tier'] ?? null; @endphp
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach (array_reverse($plans) as $plan)
                    @php
                        $isCurrent = $heldTier === $plan['tier'];
                        $featured = $plan['tier'] === 'executive';
                    @endphp
                    <article @class([
                        'flex flex-col rounded-lg border bg-surface p-5',
                        'border-accent ring-1 ring-accent' => $featured,
                        'border-border' => ! $featured,
                    ]) wire:key="plan-{{ $plan['id'] }}">
                        <h2 class="font-display font-bold text-xl text-navy-900">{{ $plan['name'] }}</h2>
                        @if ($plan['description'])
                            <p class="text-sm text-muted mt-1">{{ $plan['description'] }}</p>
                        @endif

                        <p class="mt-4 flex items-baseline gap-1">
                            <span class="font-display font-bold text-3xl text-navy-900 tabular-nums">GHS {{ number_format($plan['price_ghs'], 0) }}</span>
                            <span class="text-sm text-muted">/ {{ $plan['duration_months'] === 12 ? 'year' : $plan['duration_months'].' months' }}</span>
                        </p>

                        <ul class="mt-4 space-y-2 text-sm text-fg flex-1">
                            @foreach ($plan['perks'] as $perk)
                                <li class="flex items-start gap-2">
                                    <x-storefront.icon name="check-circle" class="w-4 h-4 mt-0.5 shrink-0 text-success" />
                                    {{ $perk }}
                                </li>
                            @endforeach
                        </ul>

                        <x-storefront.ui.button
                            type="button"
                            :variant="$featured ? 'accent' : 'primary'"
                            class="mt-5 w-full"
                            wire:click="subscribe('{{ $plan['id'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="subscribe('{{ $plan['id'] }}')"
                        >
                            <span wire:loading.remove wire:target="subscribe('{{ $plan['id'] }}')">
                                {{ $isCurrent ? 'Renew' : ($active ? 'Switch to '.$plan['name'] : 'Join '.$plan['name']) }}
                            </span>
                            <span wire:loading wire:target="subscribe('{{ $plan['id'] }}')" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Opening payment...</span>
                        </x-storefront.ui.button>
                    </article>
                @endforeach
            </div>

            <p class="text-xs text-muted mt-4">Dues are paid securely through Paystack (Mobile Money or card). Switching plan starts the new plan from today.</p>

            @if (count($history))
                <section class="mt-8">
                    <h2 class="font-display font-semibold text-lg text-navy-900 mb-3">Payment history</h2>
                    <ul class="border border-border rounded-lg divide-y divide-border bg-surface text-sm">
                        @foreach ($history as $entry)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                                <span class="font-medium text-fg">{{ $entry['plan'] }}</span>
                                <span class="text-muted">Paid {{ \Illuminate\Support\Carbon::parse($entry['paid_at'])->format('j M Y') }} · valid to {{ \Illuminate\Support\Carbon::parse($entry['expires_at'])->format('j M Y') }}</span>
                                <span class="tabular-nums">GHS {{ number_format($entry['amount_ghs'], 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endif
    </div>
</div>
