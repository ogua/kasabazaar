@extends('storefront.layouts.app')

@section('title', 'FAQs')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <x-storefront.ui.breadcrumb :items="[['label' => 'FAQs']]" />

        <div class="text-center mb-10">
            <h1 class="font-display font-bold text-3xl text-navy-900 mb-3">Frequently Asked Questions</h1>
            <p class="text-muted">Answers to the most common questions about shopping and selling on {{ config('app.name') }}.</p>
        </div>

        @php
            $faqs = [
                ['q' => 'How do I track my order?', 'a' => 'Go to My Orders in your account to see the status and tracking details for every order, per vendor.', 'link' => ['label' => 'My Orders', 'route' => 'storefront.account.orders']],
                ['q' => 'Can I buy from multiple vendors in one order?', 'a' => 'Yes. Your cart can contain items from different vendors — at checkout, we split them into separate orders per vendor but you only pay once.'],
                ['q' => 'What payment methods are accepted?', 'a' => 'We accept card and Mobile Money payments via Paystack (Ghana), and card payments via Stripe for international customers.'],
                ['q' => 'How do I become a vendor?', 'a' => 'Submit an application on our Become a Vendor page. Our team reviews applications within 2–3 business days.', 'link' => ['label' => 'Become a Vendor', 'route' => 'storefront.become-vendor']],
                ['q' => 'How do I return or cancel an order?', 'a' => 'Orders can be cancelled from your account before they\'re dispatched. For returns after delivery, contact the vendor directly from your order details page.'],
            ];
        @endphp

        <div class="space-y-3" x-data="{ open: 0 }">
            @foreach ($faqs as $index => $faq)
                <div class="border border-border rounded-lg overflow-hidden">
                    <button
                        type="button"
                        @click="open = open === {{ $index }} ? null : {{ $index }}"
                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-semibold text-sm text-fg hover:bg-surface-muted"
                    >
                        {{ $faq['q'] }}
                        <x-storefront.icon name="chevron-down" class="w-4 h-4 shrink-0 transition-transform duration-200" x-bind:class="open === {{ $index }} ? 'rotate-180' : ''" />
                    </button>
                    <div
                        x-show="open === {{ $index }}"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-cloak
                        class="px-5 pb-4 text-sm text-muted leading-relaxed"
                    >
                        {{ $faq['a'] }}
                        @isset($faq['link'])
                            <a href="{{ route($faq['link']['route']) }}" class="text-navy-500 font-medium hover:text-accent">{{ $faq['link']['label'] }}</a>
                        @endisset
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <p class="text-muted mb-4">Still have questions?</p>
            <x-storefront.ui.button href="{{ route('storefront.contact') }}" variant="primary">Contact Us</x-storefront.ui.button>
        </div>
    </div>
@endsection
