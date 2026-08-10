@extends('storefront.layouts.app')

@section('title', 'About Us')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
        <x-storefront.ui.breadcrumb :items="[['label' => 'About Us']]" />

        <div class="text-center mb-14">
            <h1 class="font-display font-bold text-3xl text-navy-900 mb-4">About {{ config('app.name') }}</h1>
            <p class="text-muted max-w-2xl mx-auto leading-relaxed">
                {{ config('app.name') }} is a multi-vendor marketplace connecting local Ghanaian vendors with shoppers across the country, part of the KasaBazaar Group of Companies' wider shipping and logistics network. Whether you're looking for electronics, fashion, groceries, or home goods, you'll find it here — sold directly by independent vendors and delivered to your door.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-5 mb-14">
            @foreach ([
                ['icon' => 'building', 'title' => 'Apply', 'desc' => 'Vendors apply to sell and are reviewed before approval.'],
                ['icon' => 'grid', 'title' => 'List', 'desc' => 'Each vendor manages their own storefront, products, and orders.'],
                ['icon' => 'cart', 'title' => 'Shop', 'desc' => 'Buy from multiple vendors in a single checkout.'],
                ['icon' => 'shield-check', 'title' => 'Pay Securely', 'desc' => 'Payments are processed securely via Paystack and Stripe.'],
            ] as $step)
                <x-storefront.ui.card class="text-center">
                    <span class="flex items-center justify-center w-12 h-12 rounded-full bg-navy-900/5 text-navy-900 mx-auto mb-3">
                        <x-storefront.icon :name="$step['icon']" class="w-5 h-5" />
                    </span>
                    <h3 class="font-semibold text-sm text-fg mb-1">{{ $step['title'] }}</h3>
                    <p class="text-xs text-muted leading-relaxed">{{ $step['desc'] }}</p>
                </x-storefront.ui.card>
            @endforeach
        </div>

        <div class="bg-navy-900 text-white rounded-lg p-10 text-center">
            <h2 class="font-display font-semibold text-2xl mb-3">Want to sell with us?</h2>
            <p class="text-white/70 mb-6 max-w-md mx-auto">Join hundreds of vendors reaching shoppers across Ghana.</p>
            <x-storefront.ui.button href="{{ route('storefront.become-vendor') }}" variant="accent" size="lg">Become a Vendor</x-storefront.ui.button>
        </div>
    </div>
@endsection
