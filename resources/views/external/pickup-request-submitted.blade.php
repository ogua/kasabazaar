<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Pickup Request Received - {{ config('group.company.name') }}</title>
    <meta name="theme-color" content="#A0043C">
    <link rel="icon" href="/images/favicon-32x32.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full mx-auto p-4">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-3">Request Received</h1>

            <p class="text-gray-600 mb-6">
                Thank you{{ $link->client ? ', '.$link->client->name : '' }}. Our team will review your pickup request
                and contact you shortly to confirm the pickup time and shipping cost.
            </p>

            @if ($canSubmitAnother)
                <a href="{{ route('public-pickup-request', $link->token) }}"
                    class="inline-block bg-[#A0043C] text-white px-6 py-2 rounded-lg font-semibold hover:opacity-90 transition">
                    Submit Another Request
                </a>
            @endif

            <div class="mt-8 pt-6 border-t border-gray-200">
                @if (file_exists(public_path('images/kasarose-logo.png')))
                    <img src="{{ asset('images/kasarose-logo.png') }}" alt="{{ config('group.company.name') }}" class="h-12 mx-auto">
                @else
                    <p class="text-red-700 font-bold">{{ config('group.company.name') }}</p>
                @endif
            </div>
        </div>
    </div>
</body>

</html>
