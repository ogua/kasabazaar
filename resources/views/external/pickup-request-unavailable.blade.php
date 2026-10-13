<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Link Unavailable - {{ config('group.company.name') }}</title>
    <meta name="theme-color" content="#A0043C">
    <link rel="icon" href="/images/favicon-32x32.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full mx-auto p-4">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-800 mb-3">
                @switch($reason)
                    @case('used') This Link Has Already Been Used @break
                    @case('expired') This Link Has Expired @break
                    @case('revoked') This Link Is No Longer Active @break
                    @default Link Not Found
                @endswitch
            </h1>

            <p class="text-gray-600 mb-6">
                @switch($reason)
                    @case('used')
                        We have already received the pickup request submitted through this link.
                        @break
                    @case('expired')
                    @case('revoked')
                        This pickup request link can no longer be used.
                        @break
                    @default
                        Please check that you copied the whole link.
                @endswitch
                If you need to send something else, please ask our team for a new link.
            </p>

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
