@extends('storefront.layouts.app')

@section('title', 'About Us')
@section('meta_description', 'Who ' . config('app.name') . ' is, how the marketplace works for shoppers and vendors, and how the KasaBazaar Group logistics network gets your order to your door.')

@section('content')
    {{-- Hero --}}
    <div class="bg-navy-900 text-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-14 md:py-20 grid md:grid-cols-2 gap-10 items-center">
            <div>
                <p class="text-accent font-semibold text-xs uppercase tracking-wide mb-3">About {{ config('app.name') }}</p>
                <h1 class="font-display font-bold text-3xl md:text-4xl leading-tight mb-5 text-balance">
                    A marketplace built by people who already move things for a living.
                </h1>
                <p class="text-white/70 leading-relaxed">
                    {{ config('app.name') }} is Ghana's multi-vendor marketplace and the retail arm of the
                    {{ config('group.parent.name') }}. Independent vendors bring the products. We bring the storefront,
                    the payment protection and — the part most marketplaces struggle with — the delivery network that
                    actually gets the parcel to your door.
                </p>
            </div>
            <div class="hidden md:flex justify-center">
                <img src="{{ asset('images/brand/logo-stacked-on-dark.png') }}" alt="" class="w-64 opacity-90">
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
        <x-storefront.ui.breadcrumb :items="[['label' => 'About Us']]" />

        {{-- Story --}}
        <section class="grid md:grid-cols-3 gap-10 mb-16">
            <div class="md:col-span-2">
                <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline mb-7">Why we exist</h2>
                <div class="space-y-4 text-muted leading-relaxed max-w-[68ch]">
                    <p>
                        The {{ config('group.parent.name') }} has spent years moving goods between the United States
                        and Ghana, and across Ghana itself. Along the way the same request kept arriving from
                        customers: <em class="text-fg not-italic font-medium">I do not want to import this myself —
                        can I just buy it from you?</em>
                    </p>
                    <p>
                        At the same time, the shops we worked with had the opposite problem. A trader in Kumasi with
                        real stock, real prices and a real reputation could sell to whoever walked past the shop, and
                        almost nobody else. Selling online meant building a site, chasing payments, and finding
                        somebody to carry a box to Tamale — three jobs that have nothing to do with the thing they are
                        actually good at.
                    </p>
                    <p>
                        {{ config('app.name') }} exists to close that gap from both ends. Vendors get a storefront,
                        payment handling and a national delivery network without owning any of it. Shoppers get to buy
                        from those vendors with the confidence that somebody stands behind the order if it goes wrong.
                    </p>
                    <p>
                        We are not a middleman that disappears once your money leaves your hands. We hold payment
                        until the order is delivered, we mediate disputes, and we own the delivery promise even when
                        the parcel is on a sister company's vehicle.
                    </p>
                </div>
            </div>

            <aside class="md:pt-16">
                <x-storefront.ui.card class="bg-surface-muted">
                    <h3 class="font-display font-semibold text-sm uppercase tracking-wide text-navy-900 mb-4">At a glance</h3>
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-muted mb-0.5">What we are</dt>
                            <dd class="text-fg font-medium">A multi-vendor marketplace</dd>
                        </div>
                        @if ($entity = config('group.company.legal_entity'))
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-muted mb-0.5">Operated by</dt>
                                <dd class="text-fg font-medium">{{ $entity }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-muted mb-0.5">Part of</dt>
                            <dd class="text-fg font-medium">
                                <a href="{{ route('storefront.group') }}" class="hover:text-accent underline underline-offset-2 decoration-border">
                                    {{ config('group.parent.name') }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-muted mb-0.5">We deliver to</dt>
                            <dd class="text-fg font-medium">Every region of Ghana</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-muted mb-0.5">Based in</dt>
                            <dd class="text-fg font-medium">{{ config('group.contact.address') ?: config('group.contact.regions') }}</dd>
                        </div>
                    </dl>
                </x-storefront.ui.card>
            </aside>
        </section>

        {{-- What we stand for --}}
        <section class="mb-16">
            <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline mb-8">What we stand for</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['icon' => 'shield-check', 'title' => 'Your money is protected', 'desc' => 'Payment is held until your order is delivered. If a vendor cannot deliver or the item is not as described, we refund you directly — you are not left negotiating alone.'],
                    ['icon' => 'building', 'title' => 'Vendors are vetted', 'desc' => 'Every seller is reviewed before they can list. We check business registration and contact details, and we remove vendors who repeatedly fail their customers.'],
                    ['icon' => 'truck', 'title' => 'Delivery is our job', 'desc' => 'Most parcels stay inside the group\'s own logistics network from dispatch to doorstep, so there is one place to ask where your order is: here.'],
                    ['icon' => 'clock', 'title' => 'No surprises at checkout', 'desc' => 'Delivery charges, timeframes and return rights are shown before you pay, not after. We do not add fees once an order is placed.'],
                ] as $value)
                    <x-storefront.ui.card>
                        <span class="flex items-center justify-center w-11 h-11 rounded-full bg-accent-soft text-accent mb-4">
                            <x-storefront.icon :name="$value['icon']" class="w-5 h-5" />
                        </span>
                        <h3 class="font-semibold text-sm text-fg mb-1.5">{{ $value['title'] }}</h3>
                        <p class="text-xs text-muted leading-relaxed">{{ $value['desc'] }}</p>
                    </x-storefront.ui.card>
                @endforeach
            </div>
        </section>

        {{-- How it works --}}
        <section class="mb-16">
            <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline mb-3">How the marketplace works</h2>
            <p class="text-muted text-sm mb-8 max-w-[68ch] leading-relaxed">
                One cart, one payment, one place to track everything — even when your order spans several independent
                businesses.
            </p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['n' => '01', 'title' => 'Vendors apply', 'desc' => 'Sellers apply, are reviewed and are approved before a single product goes live.'],
                    ['n' => '02', 'title' => 'They run their own shop', 'desc' => 'Each vendor manages their own storefront, pricing, stock and orders from their vendor portal.'],
                    ['n' => '03', 'title' => 'You buy across vendors', 'desc' => 'Fill one cart from as many vendors as you like. We split it into an order per vendor and you pay once.'],
                    ['n' => '04', 'title' => 'We get it to you', 'desc' => 'Dispatch, tracking and delivery run through the group network, with payment released to the vendor only afterwards.'],
                ] as $step)
                    <x-storefront.ui.card>
                        <span class="font-display font-bold text-2xl text-navy-200 tabular-nums block mb-3">{{ $step['n'] }}</span>
                        <h3 class="font-semibold text-sm text-fg mb-1.5">{{ $step['title'] }}</h3>
                        <p class="text-xs text-muted leading-relaxed">{{ $step['desc'] }}</p>
                    </x-storefront.ui.card>
                @endforeach
            </div>
            <p class="text-sm text-muted mt-6">
                The detail lives in our <a href="{{ route('storefront.delivery-policy') }}" class="text-navy-500 hover:text-accent underline underline-offset-2">Delivery Policy</a>,
                <a href="{{ route('storefront.returns') }}" class="text-navy-500 hover:text-accent underline underline-offset-2">Returns &amp; Refunds Policy</a> and
                <a href="{{ route('storefront.faq') }}" class="text-navy-500 hover:text-accent underline underline-offset-2">FAQs</a>.
            </p>
        </section>

        {{-- Group --}}
        <section class="mb-16">
            <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline mb-3">Part of something larger</h2>
            <p class="text-muted text-sm mb-8 max-w-[68ch] leading-relaxed">
                {{ config('app.name') }} is one of four businesses in the {{ config('group.parent.name') }}. The others
                are what make our delivery promise possible.
            </p>
            <div class="grid sm:grid-cols-3 gap-5">
                @foreach (config('group.companies') as $company)
                    <a
                        href="{{ $company['url'] }}"
                        target="_blank"
                        rel="noopener"
                        class="group block bg-surface border border-border rounded-lg p-6 hover:border-accent transition-colors duration-150"
                    >
                        <p class="text-[11px] uppercase tracking-wide text-accent font-semibold mb-2">{{ $company['role'] }}</p>
                        <h3 class="font-display font-semibold text-lg text-navy-900 mb-2 group-hover:text-accent">{{ $company['name'] }}</h3>
                        <p class="text-xs text-muted leading-relaxed mb-4">{{ $company['tagline'] }}</p>
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-navy-500 group-hover:text-accent">
                            {{ $company['url_label'] }}
                            <x-storefront.icon name="arrow-right" class="w-3.5 h-3.5" />
                        </span>
                    </a>
                @endforeach
            </div>
            <p class="text-sm text-muted mt-6">
                <a href="{{ route('storefront.group') }}" class="text-navy-500 hover:text-accent underline underline-offset-2">Read more about the group</a>
                and how the companies work together.
            </p>
        </section>

        {{-- CTAs --}}
        <div class="grid md:grid-cols-2 gap-5">
            <div class="bg-navy-900 text-white rounded-lg p-8 sm:p-10">
                <h2 class="font-display font-semibold text-xl mb-2">Want to sell with us?</h2>
                <p class="text-white/70 text-sm mb-6 leading-relaxed max-w-sm">
                    Reach shoppers across Ghana without building a site, chasing payments or running a delivery fleet.
                    Applications are reviewed within 2–3 business days.
                </p>
                <x-storefront.ui.button href="{{ route('storefront.become-vendor') }}" variant="accent" size="lg">
                    Become a Vendor
                    <x-storefront.icon name="arrow-right" class="w-4 h-4" />
                </x-storefront.ui.button>
            </div>
            <div class="bg-surface border border-border rounded-lg p-8 sm:p-10">
                <h2 class="font-display font-semibold text-xl text-navy-900 mb-2">Need to talk to someone?</h2>
                <p class="text-muted text-sm mb-6 leading-relaxed max-w-sm">
                    Whether it is an order, a return or a question about selling here, you get a person — not a
                    ticket queue that never answers.
                </p>
                <ul class="space-y-2.5 text-sm mb-6">
                    <li>
                        <a href="mailto:{{ config('group.contact.email') }}" class="inline-flex items-center gap-2 font-medium text-navy-900 hover:text-accent">
                            <x-storefront.icon name="mail" class="w-4 h-4" />
                            {{ config('group.contact.email') }}
                        </a>
                    </li>
                    <li>
                        <a href="tel:{{ config('group.contact.phone_gh_tel') }}" class="inline-flex items-center gap-2 font-medium text-navy-900 hover:text-accent">
                            <x-storefront.icon name="phone" class="w-4 h-4" />
                            {{ config('group.contact.phone_gh') }}
                        </a>
                    </li>
                    <li class="flex items-start gap-2 text-muted">
                        <x-storefront.icon name="map-pin" class="w-4 h-4 mt-0.5 shrink-0" />
                        {{ config('group.contact.address') ?: config('group.contact.regions') }}
                    </li>
                </ul>
                <x-storefront.ui.button href="{{ route('storefront.contact') }}" variant="secondary">
                    Contact Us
                    <x-storefront.icon name="arrow-right" class="w-4 h-4" />
                </x-storefront.ui.button>
            </div>
        </div>
    </div>
@endsection
