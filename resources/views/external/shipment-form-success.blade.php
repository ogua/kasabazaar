<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Submitted - Rose Door To Door Shipping</title>
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#1e40af">
    <link rel="icon" href="/images/kasabazaar-logo.png">
    <link rel="apple-touch-icon" href="/images/kasabazaar-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-lg mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-4">Shipment Details Submitted!</h1>

            <p class="text-gray-600 mb-6">
                Thank you for completing your shipment details. Our team will review your submission and process your shipment.
            </p>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-500 mb-1">Your Tracking Number</p>
                <p class="text-xl font-bold text-blue-600">{{ $shipment->tracking_number }}</p>
                <p class="text-sm text-gray-500 mt-2">Reference: {{ $shipment->shipping_reference }}</p>
            </div>

            <div class="text-sm text-gray-500">
                <p>You will receive updates about your shipment via email.</p>
                <p class="mt-2">If you have any questions, please contact us.</p>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200">
                @if (file_exists(public_path('images/kasabazaar-logo.png')))
                    <img src="{{ asset('images/kasabazaar-logo.png') }}" alt="Logo" class="h-12 mx-auto">
                @else
                    <p class="text-red-600 font-bold">Rose Door To Door Shipping</p>
                @endif
            </div>
        </div>
    </div>
</body>

</html>
