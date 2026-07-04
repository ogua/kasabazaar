<x-layouts.app>
    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                /* Chrome, Safari */
                print-color-adjust: exact;
                /* Firefox */
                margin: 0;
            }

            /* Keep the whole receipt together */
            .receipt-container {
                page-break-inside: avoid;
                page-break-before: auto;
                page-break-after: auto;
                break-inside: avoid;
            }

            /* Scale slightly to fit on one page */
            .receipt-scale {
                transform: scale(0.95);
                transform-origin: top left;
            }

            /* Remove drop shadows for clean printing */
            .no-print-shadow {
                box-shadow: none !important;
            }
        }
    </style>

    <div class="bg-gray-50 p-6">
        <div
        class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden receipt-container receipt-scale no-print-shadow">
        <!-- Header -->
        <div class="flex justify-between items-start p-6 gap-6">
            <!-- Left: Logo + business details -->
            <div class="flex flex-1 items-start gap-4">
                <!-- Logo -->
                <div class="flex-shrink-0 -mt-8">
                    <div
                    class="w-24 h-24 bg-white rounded-lg shadow-2xl flex items-center justify-center ring-4 ring-white">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Watermark" class="w-32">
                </div>
            </div>

            <div>
                <h3 class="text-gray-800 text-xl font-bold">
                    <span class="text-red-600">ROSE</span> DOOR TO DOOR SHIPPING AND DELIVERY CO
                </h3>
                <p class="text-sm text-gray-600 mt-1 font-bold">
                    <span class="text-[#4169E1]">GH</span> - Adako Jachie, Ejisu - Kumasi, Ghana
                </p>
                <p class="text-sm text-gray-600 font-bold">
                    +233 509725073 / +233 50 9725081
                </p>
                <p class="text-sm text-gray-600 mt-2 font-bold">
                    <span class="text-[#4169E1]">USA</span> - Westfield, Indiana - USA
                </p>
                <p class="text-sm text-gray-600 font-bold">
                    +1 (773) 970-0129 / +1 (574) 440-7460
                </p>
            </div>
        </div>

        <!-- Right: Amount paid -->
        <div class="w-56 mt-2 md:mt-0">
            <div class="bg-red-600 text-white rounded-lg p-4 shadow-md">
                <p class="text-xs uppercase font-extrabold tracking-wider">Amount Paid</p>
                <div class="text-2xl font-extrabold mt-2">
                    ${{ number_format($payment->amount_usd ?? $payment->amount, 2) }}
                </div>
                <div class="text-sm text-white mt-4 text-right font-extrabold">
                    @php
                        $balance = ($shipping->total ?? 0) - ($shipping->payments->sum('amount') ?? 0);
                        if ($balance < 0) {
                            $balance = 0;
                        }
                    @endphp
                    Balance: <span class="font-semibold">${{ number_format($balance, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-100"></div>

    <!-- Receipt details -->
    <div class="flex justify-between items-start p-6">
        <div>
            <p class="text-xl text-[#4169E1] font-extrabold uppercase">Receipt To</p>
            <h4 class="text-lg font-semibold text-gray-800 uppercase">
                {{ $shipping->client?->name }}
            </h4>
        </div>

        <div class="text-right">
            <p class="text-xl text-[#4169E1] font-extrabold uppercase">Receipt No</p>
            <h4 class="text-lg font-semibold">{{ $payment->payment_ref }}</h4>
            <p class="text-xl text-[#4169E1] font-extrabold">Date</p>
            <p class="text-sm font-medium">
                {{ $payment->paid_on ? date('j-M-y', strtotime($payment->paid_on)) : date('j-M-y', strtotime($payment->created_at)) }}
            </p>
        </div>
    </div>

    <div class="border-t border-gray-100"></div>

    <!-- Table -->
    <div class="p-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-red-600 text-white">
                <tr>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-left">Method</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                <tr class="h-12">
                    <td class="font-extrabold">
                        PAYMENT FOR SHIPMENT {{ $shipping->shipping_reference ?? '' }}
                    </td>
                    <td class="">{{ $payment->paying_method }}</td>
                    <td class="text-right font-extrabold">
                        ${{ number_format($payment->amount_usd ?? $payment->amount, 2) }}
                    </td>
                </tr>
                @for ($i = 0; $i < 2; $i++)
                <tr>
                    <td class="">&nbsp;</td>
                    <td class="">&nbsp;</td>
                    <td class="text-right">&nbsp;</td>
                </tr>
                @endfor

                <tr>
                    <td></td>
                    <td class="font-medium text-gray-700">Shipment Total</td>
                    <td class="text-right font-semibold">
                        ${{ number_format($shipping->total ?? 0, 2) }}
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="font-medium text-gray-700">Total Paid to Date</td>
                    <td class="text-right">
                        ${{ number_format($shipping->payments->sum('amount') ?? 0, 2) }}
                    </td>
                </tr>
                <tr class="border-t border-gray-100">
                    <td></td>
                    <td class="font-bold text-gray-800">Balance Remaining</td>
                    <td class="text-right font-extrabold text-lg">
                        ${{ number_format($balance, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="border-t border-gray-100"></div>

    <!-- Payment methods -->
    <div class="p-6">
        <p class="text-gray-600 italic">Payment can be made through the ff apps</p>
        <div class="flex justify-between items-start">
            <div class="flex-1 bg-white rounded-lg p-4 shadow-sm">
                <h5 class="text-gray-700 font-extrabold">Using Cashapp</h5>
                <p class="text-xl font-extrabold text-gray-600 mt-2">Phone #: (317) 774-6913</p>
                <p class="text-xl font-extrabold text-gray-600">Name: Rose Adel <span class="font-mono">$KasaBazaar1</span></p>
            </div>

            <div class="flex-1 bg-white rounded-lg p-4 shadow-sm">
                <h5 class="text-gray-700 font-extrabold text-right">Using Zelle</h5>
                <p class="text-xl font-extrabold text-gray-600 mt-2 text-right">Phone #: (317) 774-6913</p>
                <p class="text-xl font-extrabold text-gray-600 text-right">Name: Kasabazaar</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-red-600 text-white p-4 text-center">
        <p class="font-extrabold italic">Thank you</p>
    </div>
</div>
</div>

<script>
    // Auto trigger print when page loads
    window.addEventListener("load", function() {
        window.print();
    });
</script>
</x-layouts.app>
