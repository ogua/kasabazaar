<footer class="bg-surface-muted border-t border-border mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-10">
            <div class="col-span-2">
                <a href="{{ route('storefront.home') }}" class="inline-block mb-4">
                    <img src="{{ asset('images/brand/logo-lockup.png') }}" alt="{{ config('app.name') }}" class="h-16 w-auto">
                </a>
                <p class="text-sm text-muted leading-relaxed max-w-sm mb-4">
                    A multi-vendor marketplace connecting local sellers with shoppers across Ghana, backed by the KasaBazaar Group of Companies' shipping and logistics network.
                </p>
                <a href="mailto:{{ config('mail.from.address') }}" class="inline-flex items-center gap-2 text-sm font-medium text-navy-900 hover:text-accent">
                    <x-storefront.icon name="mail" class="w-4 h-4" />
                    {{ config('mail.from.address') }}
                </a>
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
                    <li><a href="{{ route('storefront.contact') }}" class="hover:text-accent">Contact Us</a></li>
                    <li><a href="{{ route('storefront.become-vendor') }}" class="hover:text-accent">Sell on {{ config('app.name') }}</a></li>
                    <li><a href="{{ route('storefront.faq') }}" class="hover:text-accent">FAQs</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-4">My Account</h3>
                <ul class="space-y-3 text-sm text-muted">
                    <li><a href="{{ route('storefront.account.orders') }}" class="hover:text-accent">Track My Order</a></li>
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
        </div>
    </div>

    <div class="border-t border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
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
