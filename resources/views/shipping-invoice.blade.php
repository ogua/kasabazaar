<x-layouts.app>
    <div class="relative bg-red-50 py-10">

        <!-- Background Watermark Image -->
        <div class="absolute flex justify-center items-center pointer-events-none"
            style="top: 50%; left: 0; right: 0; bottom: 50%;">
            <!--<img src="{{ asset('images/backgrounds/Vision-Kasa-Bazaar-scaled-1.jpg') }}" alt="Logo Watermark" style="opacity: 0.2;">-->
        </div>

        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg border-2 border-royalblue-600">
            <!-- Header -->
            <div class="flex justify-between items-center border-b-4 border-royalblue-600 pb-4 mb-6">
                <div>
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Watermark" style="width: 300px;">
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-red-600">Shipping Invoice</p>
                    <p class="text-base text-gray-700 font-bold">Date: 
                        <span class="text-royalblue-700">{{ $shipping->created_at }}</span>
                    </p>
                    <p class="text-base text-gray-700 font-bold">Reference #: 
                        <span class="text-royalblue-700">{{ $shipping->shipping_reference }}</span>
                    </p>
                </div>
            </div>

            <!-- Client Details -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-royalblue-700 border-b-2 border-red-500 pb-1">Client Details</h2>
                <p class="text-base text-gray-800 font-bold">Name: 
                    <span class="text-royalblue-700">{{ $shipping->client?->name }}</span>
                </p>
                <p class="text-base text-gray-800 font-bold">Address: 
                    <span class="text-royalblue-700">{{ $shipping->branch?->address }}</span>
                </p>
                <p class="text-base text-gray-800 font-bold">Phone: 
                    <span class="text-royalblue-700">{{ $shipping->client?->phone }}</span>
                </p>
                <p class="text-base text-gray-800 font-bold">Email: 
                    <span class="text-royalblue-700">{{ $shipping->client?->email }}</span>
                </p>
            </div>

            <!-- Receivers -->
            <div class="mb-6">
                <div class="space-y-6">
                    @foreach ($shipping->receivers as $receiver)
                        <div class="p-6 border-2 rounded-lg" style="background-color: #4169E1;">
                            @if ($receiver->receiver_name)
                                <p class="text-base text-white font-bold">Name: 
                                    <span>{{ $receiver->receiver_name }}</span>
                                </p>
                            @endif

                            @if ($receiver->mcountry?->name)
                                <p class="text-base text-white font-bold">Address: 
                                    <span>{{ $receiver->mcountry?->name }}, {{ $receiver->mstate?->name }}, {{ $receiver->mcity?->name }}</span>
                                </p>
                            @endif

                            @if ($receiver->receiver_phone)
                                <p class="text-base text-white font-bold">Phone: 
                                    <span>{{ $receiver->receiver_phone }}</span>
                                </p>
                            @endif

                            @if ($receiver->receiver_id_type)
                                <p class="text-base text-white font-bold">ID: 
                                    <span>{{ $receiver->receiver_id_type }} : {{ $receiver->receiver_id_number }}</span>
                                </p>
                            @endif

                            <!-- Items -->
                            <div class="mt-4">
                                <table class="w-full mt-4 text-left border-2 border-white">
                                    <thead>
                                        <tr class="bg-red-500 text-white">
                                            <th class="p-3 border text-base font-bold">#</th>
                                            <th class="p-3 border text-base font-bold">Item</th>
                                            <th class="p-3 border text-base font-bold">Box No</th>
                                            <th class="p-3 border text-base font-bold">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalQuantity = 0; @endphp
                                        @foreach ($receiver->items as $item)
                                            @php $totalQuantity += $item->quantity; @endphp
                                            <tr>
                                                <td class="p-3 border text-base text-white font-bold">{{ $loop->iteration }}</td>
                                                <td class="p-3 border text-base text-white font-bold">{{ $item->product?->name }}</td>
                                                <td class="p-3 border text-base text-white font-bold">{{ $item->box_no }}</td>
                                                <td class="p-3 border text-base text-white font-bold">{{ $item->quantity }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Totals -->
            <div class="mt-6 flex justify-end">
                <div class="text-right">
                    <p class="text-base text-gray-800 font-bold">Subtotal: 
                        <span class="text-royalblue-700">${{ number_format($shipping->total, 2) }}</span>
                    </p>
                    <p class="text-base text-gray-800 font-bold">Tax: 
                        <span class="text-red-600">0%</span>
                    </p>
                    <p class="text-2xl font-extrabold text-red-600">Total: 
                        <span class="text-royalblue-700">${{ number_format($shipping->total, 2) }}</span>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-10 text-center">
                <p class="text-lg font-bold text-royalblue-700">
                    Thank you for shipping with Rose Door To Door Packaging And Shipping Company!
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>