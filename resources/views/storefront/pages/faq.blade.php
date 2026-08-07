@extends('storefront.layouts.app')

@section('title', 'FAQs')

@section('content')
    <div class="container py-8" style="max-width:720px;">
        <h1 class="title title-simple mb-6">Frequently Asked Questions</h1>

        <div class="faq-item mb-4">
            <h4>How do I track my order?</h4>
            <p>Go to <a href="{{ route('storefront.account.orders') }}">My Orders</a> in your account to see the status and tracking details for every order, per vendor.</p>
        </div>
        <div class="faq-item mb-4">
            <h4>Can I buy from multiple vendors in one order?</h4>
            <p>Yes. Your cart can contain items from different vendors — at checkout, we split them into separate orders per vendor but you only pay once.</p>
        </div>
        <div class="faq-item mb-4">
            <h4>What payment methods are accepted?</h4>
            <p>We accept card and Mobile Money payments via Paystack (Ghana), and card payments via Stripe for international customers.</p>
        </div>
        <div class="faq-item mb-4">
            <h4>How do I become a vendor?</h4>
            <p>Submit an application on our <a href="{{ route('storefront.become-vendor') }}">Become a Vendor</a> page. Our team reviews applications within 2–3 business days.</p>
        </div>
        <div class="faq-item mb-4">
            <h4>How do I return or cancel an order?</h4>
            <p>Orders can be cancelled from your account before they're dispatched. For returns after delivery, contact the vendor directly from your order details page.</p>
        </div>
    </div>
@endsection
