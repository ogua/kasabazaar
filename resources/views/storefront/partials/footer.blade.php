{{-- Storefront footer: service strip, brand block, link columns, group strip, payments + copyright.
     The group companies row and the legal links are driven by config/group.php
     — the roster is mirrored in the KASAROSE LOGISTICS and Neoride repositories, so
     change it there too (see CLAUDE.md). --}}
<footer class="mt-auto">
    {{-- Service strip --}}
    <div class="bg-surface border-t border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="{{ route('storefront.faq') }}" class="group flex items-center gap-3">
                <x-storefront.icon name="question" class="w-8 h-8 text-navy-500 shrink-0" />
                <span class="leading-tight">
                    <span class="block text-sm font-semibold text-fg group-hover:text-accent">Help Centre</span>
                    <span class="block text-xs text-muted">Answers on orders, delivery &amp; returns</span>
                </span>
            </a>
            <a href="tel:{{ config('group.contact.phone_gh_tel') }}" class="group flex items-center gap-3">
                <x-storefront.icon name="phone" class="w-8 h-8 text-navy-500 shrink-0" />
                <span class="leading-tight">
                    <span class="block text-sm font-semibold text-fg group-hover:text-accent tabular-nums">{{ config('group.contact.phone_gh') }}</span>
                    <span class="block text-xs text-muted">Call customer support</span>
                </span>
            </a>
            <a href="mailto:{{ config('group.contact.email') }}" class="group flex items-center gap-3">
                <x-storefront.icon name="mail" class="w-8 h-8 text-navy-500 shrink-0" />
                <span class="leading-tight min-w-0">
                    <span class="block text-sm font-semibold text-fg group-hover:text-accent truncate">{{ config('group.contact.email') }}</span>
                    <span class="block text-xs text-muted">Email customer support</span>
                </span>
            </a>
        </div>
    </div>

    <div class="bg-navy-950 text-white/70">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-x-8 gap-y-10">
                <div class="col-span-2">
                    <a href="{{ route('storefront.home') }}" class="inline-block mb-4">
                        <img src="{{ asset('images/brand/logo-lockup-on-dark.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto" width="1200" height="160">
                    </a>
                    <p class="text-sm leading-relaxed max-w-sm mb-5">
                        A multi-vendor marketplace connecting local sellers with shoppers across Ghana, backed by the
                        {{ config('group.parent.name') }}' shipping and logistics network.
                    </p>

                    <div class="flex items-center gap-2">
                        <a href="#" class="flex items-center justify-center w-9 h-9 rounded-full bg-white/10 text-white hover:bg-accent" aria-label="Facebook"><x-storefront.icon name="facebook" class="w-4 h-4" /></a>
                        <a href="#" class="flex items-center justify-center w-9 h-9 rounded-full bg-white/10 text-white hover:bg-accent" aria-label="Instagram"><x-storefront.icon name="instagram" class="w-4 h-4" /></a>
                        <a href="#" class="flex items-center justify-center w-9 h-9 rounded-full bg-white/10 text-white hover:bg-accent" aria-label="Twitter"><x-storefront.icon name="twitter" class="w-4 h-4" /></a>
                    </div>
                </div>

                @foreach ([
                    'Shop' => [
                        ['label' => 'All Products', 'route' => 'storefront.shop'],
                        ['label' => 'Auctions', 'route' => 'storefront.auctions'],
                        ['label' => 'Vendors', 'route' => 'storefront.vendors'],
                        ['label' => 'My Wishlist', 'route' => 'storefront.wishlist'],
                        ['label' => 'View Cart', 'route' => 'storefront.cart'],
                    ],
                    'Customer Service' => [
                        ['label' => 'Track My Order', 'route' => 'storefront.track-order'],
                        ['label' => 'Help & FAQs', 'route' => 'storefront.faq'],
                        ['label' => 'Contact Us', 'route' => 'storefront.contact'],
                        ['label' => 'My Account', 'route' => 'storefront.account.dashboard'],
                    ],
                    'Company' => [
                        ['label' => 'About Us', 'route' => 'storefront.about'],
                        ['label' => 'Our Group', 'route' => 'storefront.group'],
                        ['label' => 'Become a Vendor', 'route' => 'storefront.become-vendor'],
                        ['label' => 'Vendor Sign In', 'route' => 'filament.vendor.auth.login'],
                    ],
                    'Legal' => [
                        ['label' => 'Privacy Policy', 'route' => 'storefront.privacy'],
                        ['label' => 'Terms of Use', 'route' => 'storefront.terms'],
                        ['label' => 'Delivery Policy', 'route' => 'storefront.delivery-policy'],
                        ['label' => 'Returns & Refunds', 'route' => 'storefront.returns'],
                        ['label' => 'Cookie Policy', 'route' => 'storefront.cookies'],
                    ],
                ] as $heading => $links)
                    <div>
                        <h3 class="font-display font-semibold text-sm text-white uppercase tracking-wide mb-4">{{ $heading }}</h3>
                        <ul class="space-y-2.5 text-sm">
                            @foreach ($links as $link)
                                <li><a href="{{ route($link['route']) }}" class="hover:text-white">{{ $link['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Group of Companies strip --}}
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex flex-col lg:flex-row lg:items-center gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-white shrink-0">
                    Part of the
                    <a href="{{ route('storefront.group') }}" class="underline decoration-accent decoration-2 underline-offset-4 hover:text-accent">
                        {{ config('group.parent.group_name') }}
                    </a>
                </p>
                <ul class="flex flex-wrap items-center gap-x-6 gap-y-2 lg:ml-auto text-sm">
                    @foreach (config('group.companies') as $company)
                        <li>
                            <a
                                href="{{ $company['url'] }}"
                                target="_blank"
                                rel="noopener"
                                class="group inline-flex items-baseline gap-1.5 hover:text-white"
                                title="{{ $company['role'] }}"
                            >
                                <span class="font-medium text-white group-hover:text-accent">{{ $company['name'] }}</span>
                                <span class="text-xs">{{ $company['url_label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
                <p>&copy; {{ now()->year }} {{ config('group.company.legal_name') }}. All rights reserved.</p>
                {{-- Only methods the checkout actually supports — see the FAQ's payment answer. --}}
                <ul class="flex flex-wrap items-center justify-center gap-2" aria-label="Accepted payment methods">
                    <li class="flex items-center gap-1.5 text-white/60 mr-1"><x-storefront.icon name="lock" class="w-3.5 h-3.5" /> Secure payments</li>
                    @foreach (['Mobile Money', 'Cards', 'Paystack', 'Stripe'] as $method)
                        <li class="rounded-sm bg-white/10 px-2 py-1 font-semibold text-white">{{ $method }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</footer>
