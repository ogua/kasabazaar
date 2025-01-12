<div class="flex items-center justify-center min-h-screen bg-slate-200">
    <div class="w-full max-w-2xl p-8 text-gray-900 bg-white rounded-lg shadow-lg dark:bg-gray-800 dark:text-white">

        <div class="flex items-center justify-center">
            <img
                alt="UniMAC logo"
                src="{{ asset('images/favicon.png') }}"
                style="height: 4rem;" class="flex items-center justify-center mb-4 text-center fi-logo"
            />

        </div>

        <h1 class="text-2xl font-bold tracking-tight text-center fi-simple-header-heading text-gray-950 dark:text-white">
            Payment Successfully!
        </h1>


        <div class="p-4 text-center border border-dashed rounded-md bg-gray-50">
            <p class="text-center text-gray-600">Thank you for your payment.</p>
            <p class="text-center text-gray-600">Please check your email and phone for your PIN and serial number.</p>
            <p class="text-center text-gray-600">Use these details to proceed with the admission process.</p>
            <p class="text-center text-gray-600">Thank you for choosing Unimac</p>
            <div class="mt-5" style="margin-top: 20px;">
                <x-filament::button
                    href="/admission"
                    tag="a"
                    icon="heroicon-m-lock-closed"
                    style="background-color: #A0043C;"
                >
                    Login Admission Portal
                </x-filament::button>
            </div>
        </div>
    </div>
</div>

