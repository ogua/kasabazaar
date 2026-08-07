@extends('storefront.layouts.app')

@section('title', 'About Us')

@section('content')
    <div class="container py-8" style="max-width:800px;">
        <h1 class="title title-simple mb-4">About {{ config('app.name') }}</h1>
        <p>{{ config('app.name') }} is a multi-vendor marketplace connecting local Ghanaian vendors with shoppers across the country. Whether you're looking for electronics, fashion, groceries, or home goods, you'll find it here — sold directly by independent vendors and delivered to your door.</p>

        <h3 class="mt-6">How it works</h3>
        <ul>
            <li>Vendors apply to sell on {{ config('app.name') }} and are reviewed before approval.</li>
            <li>Each vendor manages their own storefront, products, and orders.</li>
            <li>Shoppers can buy from multiple vendors in a single checkout.</li>
            <li>Payments are processed securely via Paystack and Stripe.</li>
        </ul>

        <h3 class="mt-6">Want to sell with us?</h3>
        <p><a href="{{ route('storefront.become-vendor') }}" class="btn btn-dark btn-rounded">Become a Vendor</a></p>
    </div>
@endsection
