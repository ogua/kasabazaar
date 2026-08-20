<div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
    <x-storefront.ui.breadcrumb :items="[['label' => 'Contact Us']]" />

    <div class="text-center mb-12">
        <h1 class="font-display font-bold text-3xl text-navy-900 mb-3">Get in Touch</h1>
        <p class="text-muted max-w-xl mx-auto">Have a question about an order, a vendor application, or the marketplace in general? Send us a message and we'll get back to you.</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-12">
        <x-storefront.ui.card class="text-center">
            <span class="flex items-center justify-center w-12 h-12 rounded-full bg-navy-900/5 text-navy-900 mx-auto mb-3">
                <x-storefront.icon name="mail" class="w-5 h-5" />
            </span>
            <h2 class="font-semibold text-sm text-fg mb-1">Email</h2>
            <a href="mailto:{{ config('group.contact.email') }}" class="text-sm text-navy-500 hover:text-accent">{{ config('group.contact.email') }}</a>
        </x-storefront.ui.card>

        <x-storefront.ui.card class="text-center">
            <span class="flex items-center justify-center w-12 h-12 rounded-full bg-navy-900/5 text-navy-900 mx-auto mb-3">
                <x-storefront.icon name="phone" class="w-5 h-5" />
            </span>
            <h2 class="font-semibold text-sm text-fg mb-1">Phone</h2>
            <a href="tel:{{ config('group.contact.phone_gh_tel') }}" class="block text-sm text-navy-500 hover:text-accent">{{ config('group.contact.phone_gh') }}</a>
            <a href="tel:{{ config('group.contact.phone_us_tel') }}" class="block text-sm text-navy-500 hover:text-accent">{{ config('group.contact.phone_us') }}</a>
        </x-storefront.ui.card>

        @if ($whatsapp = config('services.whatsapp.number'))
            <x-storefront.ui.card class="text-center">
                <span class="flex items-center justify-center w-12 h-12 rounded-full bg-navy-900/5 text-navy-900 mx-auto mb-3">
                    <x-storefront.icon name="whatsapp" class="w-5 h-5" />
                </span>
                <h2 class="font-semibold text-sm text-fg mb-1">WhatsApp</h2>
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="text-sm text-navy-500 hover:text-accent">Chat with us</a>
            </x-storefront.ui.card>
        @endif

        <x-storefront.ui.card class="text-center">
            <span class="flex items-center justify-center w-12 h-12 rounded-full bg-navy-900/5 text-navy-900 mx-auto mb-3">
                <x-storefront.icon name="map-pin" class="w-5 h-5" />
            </span>
            <h2 class="font-semibold text-sm text-fg mb-1">Based In</h2>
            <p class="text-sm text-muted">{{ config('group.contact.address') ?: config('group.contact.regions') }}</p>
        </x-storefront.ui.card>
    </div>

    <div class="grid md:grid-cols-2 gap-10">
        <div>
            <h2 class="font-display font-semibold text-xl text-navy-900 wake-underline mb-6">Frequently Asked</h2>
            <div class="space-y-3">
                <p class="text-sm text-muted">Looking for quick answers about orders, payments, or becoming a vendor? Visit our <a href="{{ route('storefront.faq') }}" class="text-navy-500 font-medium hover:text-accent">FAQ page</a> — most questions are answered there.</p>
                <p class="text-sm text-muted">For order-specific issues, sign in and check <a href="{{ route('storefront.account.orders') }}" class="text-navy-500 font-medium hover:text-accent">My Orders</a> first — tracking and status details are all there.</p>
                <p class="text-sm text-muted">Delivery timeframes are set out in our <a href="{{ route('storefront.delivery-policy') }}" class="text-navy-500 font-medium hover:text-accent">Delivery Policy</a>, and return rights in our <a href="{{ route('storefront.returns') }}" class="text-navy-500 font-medium hover:text-accent">Returns &amp; Refunds Policy</a>.</p>
                <p class="text-sm text-muted">Looking for a different {{ config('group.parent.name') }} business — freight, real estate or mobility? See <a href="{{ route('storefront.group') }}" class="text-navy-500 font-medium hover:text-accent">Our Group</a>.</p>
            </div>
        </div>

        <div>
            <h2 class="font-display font-semibold text-xl text-navy-900 wake-underline mb-6">Send Us a Message</h2>

            @if ($sent)
                <x-storefront.ui.alert variant="success">Thanks for reaching out! We'll get back to you as soon as we can.</x-storefront.ui.alert>
            @else
                @if ($error)
                    <x-storefront.ui.alert variant="error" class="mb-5">{{ $error }}</x-storefront.ui.alert>
                @endif

                <form wire:submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Your Name</label>
                        <input type="text" wire:model="name" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        @error('name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Your Email</label>
                        <input type="email" wire:model="email" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        @error('email') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Your Message</label>
                        <textarea wire:model="message" rows="5" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"></textarea>
                        @error('message') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <x-storefront.ui.button type="submit" variant="accent" size="lg" wire:loading.attr="disabled" wire:target="submit" class="w-full">
                        <span wire:loading.remove wire:target="submit">Send Message</span>
                        <span wire:loading wire:target="submit" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Sending...</span>
                    </x-storefront.ui.button>
                </form>
            @endif
        </div>
    </div>
</div>
