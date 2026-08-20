@extends('storefront.layouts.app')

@section('title', 'Our Group')
@section('meta_description', config('app.name') . ' is the ecommerce marketplace of the KasaBazaar Group of Companies, alongside KasaBazaar, RDD Shipping and Neoride Africa.')

@section('content')
    <div class="bg-navy-900 text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-14 md:py-20">
            <p class="text-accent font-semibold text-xs uppercase tracking-wide mb-3">{{ config('group.parent.name') }}</p>
            <h1 class="font-display font-bold text-3xl md:text-4xl mb-4 text-balance max-w-2xl">
                One group. Four businesses. One promise about getting things where they need to be.
            </h1>
            <p class="text-white/70 leading-relaxed max-w-2xl">
                The group did not start as a marketplace. It started as a shipping company that kept being asked the
                same question by its customers: <em>can you get this for me too?</em> Today it moves freight,
                property and people as well as products — and {{ config('app.name') }} is the shop front of it.
            </p>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
        <x-storefront.ui.breadcrumb :items="[['label' => 'Our Group']]" />

        {{-- This company --}}
        <section class="mb-14">
            <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline mb-8">Where {{ config('app.name') }} fits</h2>
            <div class="grid md:grid-cols-3 gap-6 items-start">
                <div class="md:col-span-2 space-y-4 text-muted leading-relaxed">
                    <p>
                        {{ config('app.name') }} is the group's <strong class="text-fg">ecommerce marketplace</strong>.
                        Independent Ghanaian vendors list their products here; we handle the storefront, take payment,
                        hold it until the order is delivered, and put the group's logistics network behind the parcel.
                    </p>
                    <p>
                        That last part is the reason the marketplace exists at all. Most online sellers in Ghana can
                        build a catalogue; far fewer can promise a shopper in Tamale that an item from a shop in Accra
                        will actually turn up. Our sister companies already move goods across the country and in from
                        abroad every day — {{ config('app.name') }} simply points that capacity at retail.
                    </p>
                    <p>
                        Each company below is a separate business with its own contracts and its own terms. We share
                        infrastructure and standards, not liabilities.
                    </p>
                </div>
                <x-storefront.ui.card class="bg-surface-muted">
                    <img src="{{ asset('images/brand/logo-lockup.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto mb-4">
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-muted mb-0.5">Role in the group</dt>
                            <dd class="text-fg font-medium">{{ config('group.company.role') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-muted mb-0.5">Operating since</dt>
                            <dd class="text-fg font-medium">{{ config('group.company.founded') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-muted mb-0.5">Markets</dt>
                            <dd class="text-fg font-medium">{{ config('group.contact.regions') }}</dd>
                        </div>
                    </dl>
                </x-storefront.ui.card>
            </div>
        </section>

        {{-- Sister companies --}}
        <section class="mb-14">
            <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline mb-8">The other companies</h2>
            <div class="space-y-5">
                @foreach (config('group.companies') as $company)
                    <x-storefront.ui.card padding="p-6 sm:p-8">
                        <div class="sm:flex sm:items-start sm:gap-8">
                            <div class="sm:flex-1">
                                <p class="text-xs uppercase tracking-wide text-accent font-semibold mb-1.5">{{ $company['role'] }}</p>
                                <h3 class="font-display font-semibold text-xl text-navy-900 mb-2">{{ $company['name'] }}</h3>
                                <p class="text-sm font-medium text-navy-500 mb-3">{{ $company['tagline'] }}</p>
                                <p class="text-sm text-muted leading-relaxed">{{ $company['description'] }}</p>
                            </div>
                            <div class="mt-5 sm:mt-0 shrink-0">
                                <x-storefront.ui.button
                                    href="{{ $company['url'] }}"
                                    variant="secondary"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Visit {{ $company['url_label'] }}
                                    <x-storefront.icon name="arrow-right" class="w-4 h-4" />
                                </x-storefront.ui.button>
                            </div>
                        </div>
                    </x-storefront.ui.card>
                @endforeach
            </div>
        </section>

        {{-- What the group means for shoppers --}}
        <section class="mb-14">
            <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline mb-8">What that means when you order</h2>
            <div class="grid sm:grid-cols-3 gap-5">
                @foreach ([
                    ['icon' => 'truck', 'title' => 'One network, not four handoffs', 'desc' => 'A parcel moving from a vendor in Accra to a doorstep in Ejisu stays inside the group the whole way. Fewer handoffs is fewer places to lose it.'],
                    ['icon' => 'shield-check', 'title' => 'One point of contact', 'desc' => 'Whoever is physically carrying your order, you raise it with us. We do not send you to chase a courier.'],
                    ['icon' => 'box', 'title' => 'Reach beyond the vendor', 'desc' => 'A small vendor with no delivery fleet can still sell nationwide, because ours is the fleet behind the listing.'],
                ] as $point)
                    <x-storefront.ui.card>
                        <span class="flex items-center justify-center w-11 h-11 rounded-full bg-navy-900/5 text-navy-900 mb-4">
                            <x-storefront.icon :name="$point['icon']" class="w-5 h-5" />
                        </span>
                        <h3 class="font-semibold text-sm text-fg mb-1.5">{{ $point['title'] }}</h3>
                        <p class="text-xs text-muted leading-relaxed">{{ $point['desc'] }}</p>
                    </x-storefront.ui.card>
                @endforeach
            </div>
        </section>

        <div class="rounded-lg bg-navy-900 text-white p-8 sm:p-10 flex flex-col sm:flex-row sm:items-center gap-6">
            <div class="sm:flex-1">
                <h2 class="font-display font-semibold text-xl mb-2">See the whole group</h2>
                <p class="text-white/70 text-sm leading-relaxed max-w-lg">
                    The {{ config('group.parent.name') }} site covers the founding company's shipping, real estate and
                    property management work, and profiles every business in the group.
                </p>
            </div>
            <x-storefront.ui.button
                href="{{ config('group.parent.companies_url') }}"
                variant="accent"
                size="lg"
                target="_blank"
                rel="noopener"
                class="shrink-0"
            >
                Visit the group site
                <x-storefront.icon name="arrow-right" class="w-4 h-4" />
            </x-storefront.ui.button>
        </div>
    </div>
@endsection
