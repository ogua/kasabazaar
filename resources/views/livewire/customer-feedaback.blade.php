<div>
    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    <div class="min-h-screen bg-gradient-to-br from-red-50 via-white to-blue-50 py-8 px-4">
        <!-- Header Section -->
        <div class="max-w-3xl mx-auto mb-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border-2 border-[#4169E1]">
                <!-- Top Banner -->
                <div class="bg-gradient-to-r from-[#4169E1] to-[#2d4ea8] px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div class="bg-white rounded-lg p-2 shadow-md">
                                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-12 w-auto">
                            </div>
                            <div>
                                <h1 class="text-white text-xl font-bold">Customer Feedback</h1>
                                <p class="text-blue-100 text-sm">We value your opinion</p>
                            </div>
                        </div>
                        <div class="text-right text-white">
                            <p class="text-sm font-medium">Rose Door To Door</p>
                            <p class="text-xs text-blue-100">Shipping & Delivery</p>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="px-6 py-4 bg-gradient-to-r from-red-500 to-red-600 text-white">
                    <p class="text-center font-semibold">
                        Your feedback helps us improve our services. Please take a moment to share your experience.
                    </p>
                </div>
            </div>
        </div>

        @if($submitted)
            <!-- Success State -->
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border-2 border-green-500">
                    <div class="p-8 text-center">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Thank You!</h2>
                        <p class="text-gray-600 mb-6">
                            Your feedback has been submitted successfully. We appreciate you taking the time to share your experience with us.
                        </p>
                        <div class="bg-blue-50 rounded-lg p-4 mb-6">
                            <p class="text-sm text-[#4169E1]">
                                Our team will review your feedback and may reach out if we need additional information.
                            </p>
                        </div>
                        <button
                            wire:click="submitAnother"
                            class="inline-flex items-center px-6 py-3 bg-[#4169E1] text-white font-semibold rounded-lg hover:bg-[#2d4ea8] transition-colors duration-200 shadow-md"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Submit Another Feedback
                        </button>
                    </div>

                    <!-- Footer -->
                    <div class="bg-red-600 text-white p-4 text-center">
                        <p class="font-semibold">Thank you for choosing Rose Door To Door Shipping!</p>
                        <p class="text-sm text-red-100 mt-1">Your satisfaction is our priority</p>
                    </div>
                </div>
            </div>
        @else
            <!-- Feedback Form -->
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                    <form wire:submit="create" class="p-6 space-y-6">
                        {{ $this->form }}

                        <!-- Submit Button -->
                        <div class="pt-6 border-t border-gray-200">
                            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                                <p class="text-sm text-gray-500">
                                    <span class="text-red-500">*</span> Required fields
                                </p>
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75 cursor-not-allowed"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-[#4169E1] to-[#2d4ea8] text-white font-bold rounded-lg hover:from-[#2d4ea8] hover:to-[#1e3a7a] transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-75 disabled:cursor-not-allowed"
                                >
                                    <!-- Loading Spinner -->
                                    <svg wire:loading wire:target="create" class="w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <!-- Default Icon -->
                                    <svg wire:loading.remove wire:target="create" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="create">Submit Feedback</span>
                                    <span wire:loading wire:target="create">Submitting...</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="bg-gradient-to-r from-red-500 to-red-600 text-white p-4">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-2">
                            <p class="font-semibold text-center sm:text-left">Rose Door To Door Shipping & Delivery</p>
                            <div class="flex items-center gap-4 text-sm">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    +1 (773) 970-0129
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    info@kasabazaar.com
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Contact Info Card -->
        <div class="max-w-3xl mx-auto mt-8">
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-[#4169E1] mb-4 text-center">Need Immediate Assistance?</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="font-bold text-gray-800">Ghana Office</p>
                        <p class="text-sm text-gray-600">Adako Jachie, Ejisu - Kumasi</p>
                        <p class="text-sm text-[#4169E1] font-semibold mt-1">+233 509725073</p>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <p class="font-bold text-gray-800">USA Office</p>
                        <p class="text-sm text-gray-600">Westfield, Indiana</p>
                        <p class="text-sm text-red-600 font-semibold mt-1">+1 (773) 970-0129</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @livewire('notifications')
</div>
