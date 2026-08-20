@extends('storefront.layouts.app')

@section('title', 'FAQs')
@section('meta_description', 'Answers to common questions about ordering, payment, delivery, returns and selling on ' . config('app.name') . '.')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <x-storefront.ui.breadcrumb :items="[['label' => 'FAQs']]" />

        <div class="text-center mb-10">
            <h1 class="font-display font-bold text-3xl text-navy-900 mb-3">Frequently Asked Questions</h1>
            <p class="text-muted">Answers to the most common questions about shopping and selling on {{ config('app.name') }}.</p>
        </div>

        @php
            // Grouped so the page stays scannable as the list grows. Delivery
            // answers must match config('group.delivery') and the Delivery
            // Policy page — change both together.
            $faqGroups = [
                'Ordering & payment' => [
                    [
                        'q' => 'Can I buy from multiple vendors in one order?',
                        'a' => 'Yes. Your cart can contain items from different vendors — at checkout we split them into a separate order per vendor, but you only pay once.',
                    ],
                    [
                        'q' => 'What payment methods are accepted?',
                        'a' => 'Card and Mobile Money payments via Paystack (Ghana), and card payments via Stripe for international customers. We never see or store your full card number.',
                    ],
                    [
                        'q' => 'When is my payment released to the vendor?',
                        'a' => 'Not immediately. We hold the payment until your order is delivered, which is what lets us refund you directly when something goes wrong.',
                    ],
                    [
                        'q' => 'Are prices inclusive of tax?',
                        'a' => 'Prices are shown in Ghana Cedis and include VAT where it applies. Delivery is charged separately and shown in full before you pay.',
                    ],
                ],
                'Delivery' => [
                    [
                        'q' => 'How long will my order take?',
                        'a' => 'Counting business days from dispatch: 1–3 days in Accra and Kumasi, 3–7 days elsewhere in Ghana, 5–10 days for bulky items, and 10–21 days for items shipped in from abroad. Add the vendor handling time shown on the product page.',
                        'link' => ['label' => 'Read the Delivery Policy', 'route' => 'storefront.delivery-policy'],
                    ],
                    [
                        'q' => 'How do I track my order?',
                        'a' => 'Go to My Orders in your account to see the status and tracking details for every order, per vendor. Guests can use the order tracking page with an order number and email address.',
                        'link' => ['label' => 'Track an order', 'route' => 'storefront.track-order'],
                    ],
                    [
                        'q' => 'Who actually delivers my order?',
                        'a' => 'The vendor, one of our courier partners, or one of the group\'s own companies — RDD Shipping for road freight and inbound international parcels, Neoride Africa for last-mile drops in Kumasi and Ejisu. Whoever carries it, you raise any problem with us.',
                        'link' => ['label' => 'About our group', 'route' => 'storefront.group'],
                    ],
                    [
                        'q' => 'What if nobody is home?',
                        'a' => 'Our rider calls ahead and will attempt delivery twice more over the following two business days. After three failed attempts the parcel goes back to the vendor and a second delivery charge applies to redeliver it.',
                    ],
                ],
                'Returns & refunds' => [
                    [
                        'q' => 'How do I cancel an order?',
                        'a' => 'Free of charge from My Orders, any time before the vendor dispatches it. Once it shows as Dispatched it becomes a return instead.',
                        'link' => ['label' => 'My Orders', 'route' => 'storefront.account.orders'],
                    ],
                    [
                        'q' => 'How long do I have to return something?',
                        'a' => config('group.returns.window_days').' days from delivery for an item that is faulty, damaged, incomplete, counterfeit or materially different from its listing. For anything that arrives visibly damaged or wrong, tell us within 48 hours and we settle it at no cost to you.',
                        'link' => ['label' => 'Read the Returns Policy', 'route' => 'storefront.returns'],
                    ],
                    [
                        'q' => 'When will I get my refund?',
                        'a' => 'Approved refunds are issued to your original payment method within '.config('group.returns.refund_days').' days. Paystack refunds usually land within 3–7 business days; Stripe card refunds within 5–10, depending on your bank.',
                    ],
                    [
                        'q' => 'What if the vendor refuses my return?',
                        'a' => 'Escalate it to us from the same order screen. We review the listing, photos, messages and delivery record and decide within five business days — and because we hold the payment, we can refund you without the vendor agreeing.',
                    ],
                ],
                'Selling on '.config('app.name') => [
                    [
                        'q' => 'How do I become a vendor?',
                        'a' => 'Submit an application on our Become a Vendor page. Our team reviews applications within 2–3 business days.',
                        'link' => ['label' => 'Become a Vendor', 'route' => 'storefront.become-vendor'],
                    ],
                    [
                        'q' => 'What does it cost to sell here?',
                        'a' => 'There is no listing fee. We deduct a commission plus payment-processing costs from each completed sale; the rates are set out in your vendor agreement before you accept it.',
                    ],
                    [
                        'q' => 'Do I need my own delivery fleet?',
                        'a' => 'No — that is the point. You can deliver locally yourself if you prefer, or hand fulfilment to the group logistics network and sell nationwide from day one.',
                    ],
                    [
                        'q' => 'When do I get paid?',
                        'a' => 'Settlement runs after the order is delivered and the return window has passed, on the schedule in your vendor agreement. Earnings and payouts are visible in your vendor portal at all times.',
                    ],
                ],
            ];

            $index = 0;
        @endphp

        <div x-data="{ open: null }" class="space-y-10">
            @foreach ($faqGroups as $groupTitle => $faqs)
                <section>
                    <h2 class="font-display font-semibold text-sm uppercase tracking-wide text-navy-900 mb-4">{{ $groupTitle }}</h2>
                    <div class="space-y-3">
                        @foreach ($faqs as $faq)
                            @php $index++; @endphp
                            <div class="border border-border rounded-lg overflow-hidden">
                                <button
                                    type="button"
                                    @click="open = open === {{ $index }} ? null : {{ $index }}"
                                    class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-semibold text-sm text-fg hover:bg-surface-muted"
                                    :aria-expanded="open === {{ $index }}"
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
                </section>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <p class="text-muted mb-4">Still have questions?</p>
            <x-storefront.ui.button href="{{ route('storefront.contact') }}" variant="primary">Contact Us</x-storefront.ui.button>
        </div>
    </div>
@endsection
