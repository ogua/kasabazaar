<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expired - Rose Door To Door Shipping</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-lg mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-4">Link Expired or Invalid</h1>

            <p class="text-gray-600 mb-6">
                This shipment form link has either expired, already been completed, or is invalid.
            </p>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    If you believe this is an error, please contact our support team with your shipment reference number.
                </p>
            </div>

            <div class="text-sm text-gray-500">
                <p>Need help? Contact us:</p>
                <p class="mt-1 text-blue-600">+1 (773) 970-0129</p>
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
