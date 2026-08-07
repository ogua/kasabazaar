@extends('storefront.layouts.app')

@section('title', 'Contact Us')

@section('content')
    <div class="container py-8" style="max-width:600px;">
        <h1 class="title title-simple mb-4">Contact Us</h1>
        <p>Have a question about an order, a vendor application, or anything else? Reach out and we'll get back to you.</p>

        <ul class="list-unstyled">
            <li><strong>Email:</strong> <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a></li>
            @if ($whatsapp = config('services.whatsapp.number'))
                <li><strong>WhatsApp:</strong> <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">{{ $whatsapp }}</a></li>
            @endif
        </ul>
    </div>
@endsection
