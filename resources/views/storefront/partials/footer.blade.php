{{-- Storefront footer: brand block, link columns, legal column, group strip.
     The group companies row and the legal links are driven by config/group.php
     — the roster is mirrored in the group site, RDD Shipping and Neoride
     repositories, so change it there too (see CLAUDE.md). --}}
<footer class="bg-surface-muted border-t border-border mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-x-8 gap-y-10">
            <div class="col-span-2">
                <a href="{{ route('storefront.home') }}" class="inline-block mb-4">
                    <img src="{{ asset('images/brand/logo-lockup.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto">
                </a>
                <p class="text-sm text-muted leading-relaxed max-w-sm mb-5">
                    A multi-vendor marketplace connecting local sellers with shoppers across Ghana, backed by the
                    {{ config('group.parent.name') }}' shipping and logistics network.
                </p>

                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="mailto:{{ config('group.contact.email') }}" class="inline-flex items-center gap-2 font-medium text-navy-900 hover:text-accent">
                            <x-storefront.icon name="mail" class="w-4 h-4 shrink-0" />
                            {{ config('group.contact.email') }}
                        </a>
                    </li>
                    <li>
                        <a href="tel:{{ config('group.contact.phone_gh_tel') }}" class="inline-flex items-center gap-2 font-medium text-navy-900 hover:text-accent">
                            <x-storefront.icon name="phone" class="w-4 h-4 shrink-0" />
                            {{ config('group.contact.phone_gh') }}
                        </a>
                    </li>
                </ul>

                <div class="flex items-center gap-3 mt-5">
                    <a href="#" class="flex items-center justify-center w-9 h-9 rounded-full bg-navy-900 text-white hover:bg-accent" aria-label="Facebook"><x-storefront.icon name="facebook" class="w-4 h-4" /></a>
                    <a href="#" class="flex items-center justify-center w-9 h-9 rounded-full bg-navy-900 text-white hover:bg-accent" aria-label="Instagram"><x-storefront.icon name="instagram" class="w-4 h-4" /></a>
                    <a href="#" class="flex items-center justify-center w-9 h-9 rounded-full bg-navy-900 text-white hover:bg-accent" aria-label="Twitter"><x-storefront.icon name="twitter" class="w-4 h-4" /></a>
                </div>
            </div>

            <div>
                <h3 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-4">Company</h3>
                <ul class="space-y-3 text-sm text-muted">
                    <li><a href="{{ route('storefront.about') }}" class="hover:text-accent">About Us</a></li>
                    <li><a href="{{ route('storefront.group') }}" class="hover:text-accent">Our Group</a></li>
                    <li><a href="{{ route('storefront.contact') }}" class="hover:text-accent">Contact Us</a></li>
                    <li><a href="{{ route('storefront.become-vendor') }}" class="hover:text-accent">Sell on {{ config('app.name') }}</a></li>
                    <li><a href="{{ route('storefront.faq') }}" class="hover:text-accent">FAQs</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-4">My Account</h3>
                <ul class="space-y-3 text-sm text-muted">
                    <li><a href="{{ route('storefront.track-order') }}" class="hover:text-accent">Track My Order</a></li>
                    <li><a href="{{ route('storefront.cart') }}" class="hover:text-accent">View Cart</a></li>
                    <li><a href="{{ route('storefront.login') }}" class="hover:text-accent">Sign In</a></li>
                    <li><a href="{{ route('storefront.wishlist') }}" class="hover:text-accent">My Wishlist</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-4">Vendors</h3>
                <ul class="space-y-3 text-sm text-muted">
                    <li><a href="{{ route('storefront.vendors') }}" class="hover:text-accent">All Vendors</a></li>
                    <li><a href="{{ route('storefront.become-vendor') }}" class="hover:text-accent">Become a Vendor</a></li>
                    <li><a href="{{ route('filament.vendor.auth.login') }}" class="hover:text-accent">Vendor Sign In</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-4">Legal</h3>
                <ul class="space-y-3 text-sm text-muted">
                    <li><a href="{{ route('storefront.privacy') }}" class="hover:text-accent">Privacy Policy</a></li>
                    <li><a href="{{ route('storefront.terms') }}" class="hover:text-accent">Terms of Use</a></li>
                    <li><a href="{{ route('storefront.delivery-policy') }}" class="hover:text-accent">Delivery Policy</a></li>
                    <li><a href="{{ route('storefront.returns') }}" class="hover:text-accent">Returns &amp; Refunds</a></li>
                    <li><a href="{{ route('storefront.cookies') }}" class="hover:text-accent">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Group of Companies strip --}}
    <div class="border-t border-border bg-surface">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-7">
            <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-900 shrink-0">
                    Part of the
                    <a href="{{ config('group.parent.companies_url') }}" target="_blank" rel="noopener" class="underline decoration-accent decoration-2 underline-offset-4 hover:text-accent">
                        {{ config('group.parent.name') }}
                    </a>
                </p>
                <ul class="flex flex-wrap items-center gap-x-6 gap-y-2 lg:ml-auto text-sm">
                    @foreach (config('group.companies') as $company)
                        <li>
                            <a
                                href="{{ $company['url'] }}"
                                target="_blank"
                                rel="noopener"
                                class="group inline-flex items-baseline gap-1.5 text-muted hover:text-accent"
                                title="{{ $company['role'] }}"
                            >
                                <span class="font-medium text-navy-900 group-hover:text-accent">{{ $company['name'] }}</span>
                                <span class="text-xs">{{ $company['url_label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted">
            <p>&copy; {{ now()->year }} {{ config('group.company.legal_name') }}. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <span>Secure payments</span>
                <div class="flex items-center gap-2">
                    <x-storefront.icon name="shield-check" class="w-4 h-4 text-navy-900" />
                    <x-storefront.icon name="truck" class="w-4 h-4 text-navy-900" />
                </div>
            </div>
        </div>
    </div>
</footer>
