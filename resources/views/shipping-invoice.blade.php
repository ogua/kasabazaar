<x-layouts.app>
    <div class="relative bg-red-50 py-10">

        <!-- Background Watermark Image -->
        <div class="absolute flex justify-center items-center pointer-events-none"
            style="top: 50%; left: 0; right: 0; bottom: 50%;">
            <!--<img src="{{ asset('images/backgrounds/Vision-Kasa-Bazaar-scaled-1.jpg') }}" alt="Logo Watermark" style="opacity: 0.2;">-->
        </div>

        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg border border-royalblue-500">
            <!-- Header -->
            <div class="flex justify-between items-center border-b-2 border-royalblue-500 pb-4 mb-6">
                <div>
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Watermark" style="width: 300px;">
                </div>
                <div class="text-right">
                    <p class="text-sm text-red-600"><span class="font-medium">Shipping Invoice</span></p>
                    <p class="text-sm text-gray-700">Date: <span class="font-medium text-royalblue-600">{{ $shipping->created_at }}</span></p>
                    <p class="text-sm text-gray-700">Reference #: <span class="font-medium text-royalblue-600">{{ $shipping->shipping_reference }}</span></p>
                </div>
            </div>

            <!-- Client Details -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-royalblue-700 border-b border-red-500">Client Details</h2>
                <p class="text-sm text-gray-700">Name: <span class="font-medium text-royalblue-600">{{ $shipping->client?->name }}</span></p>
                <p class="text-sm text-gray-700">Address: <span class="font-medium text-royalblue-600">{{ $shipping->branch?->address }}</span></p>
                <p class="text-sm text-gray-700">Phone: <span class="font-medium text-royalblue-600">{{ $shipping->client?->phone }}</span></p>
                <p class="text-sm text-gray-700">Email: <span class="font-medium text-royalblue-600">{{ $shipping->client?->email }}</span></p>
            </div>

            <!-- Receivers -->
            <div class="mb-6">
                <div class="space-y-4">
                    @foreach ($shipping->receivers as $receiver)
                        <div class="p-4 border rounded-lg" style="background-color: #4169E1;">
                            @if ($receiver->receiver_name)
                                <p class="text-sm text-white">Name: <span class="font-medium">{{ $receiver->receiver_name }}</span></p>
                            @endif

                            @if ($receiver->mcountry?->name)
                                <p class="text-sm text-white">Address: 
                                    <span class="font-medium">{{ $receiver->mcountry?->name }}, {{ $receiver->mstate?->name }}, {{ $receiver->mcity?->name }}</span>
                                </p>
                            @endif

                            @if ($receiver->receiver_phone)
                                <p class="text-sm text-white">Phone: <span class="font-medium">{{ $receiver->receiver_phone }}</span></p>
                            @endif

                            @if ($receiver->receiver_id_type)
                                <p class="text-sm text-white">ID: <span class="font-medium">{{ $receiver->receiver_id_type }} : {{ $receiver->receiver_id_number }}</span></p>
                            @endif

                            <!-- Items -->
                            <div class="mt-4">
                                <table class="w-full mt-4 text-left border border-red-500">
                                    <thead>
                                        <tr class="bg-red-500 text-white">
                                            <th class="p-2 border text-sm font-medium">#</th>
                                            <th class="p-2 border text-sm font-medium">Item</th>
                                            <th class="p-2 border text-sm font-medium">Box No</th>
                                            <th class="p-2 border text-sm font-medium">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalQuantity = 0; @endphp
                                        @foreach ($receiver->items as $item)
                                            @php $totalQuantity += $item->quantity; @endphp
                                            <tr>
                                                <td class="p-2 border text-sm text-white">{{ $loop->iteration }}</td>
                                                <td class="p-2 border text-sm text-white">{{ $item->product?->name }}</td>
                                                <td class="p-2 border text-sm text-white">{{ $item->box_no }}</td>
                                                <td class="p-2 border text-sm text-white">{{ $item->quantity }}</td>
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
                <div>
                    <p class="text-sm text-gray-700">Subtotal: <span class="font-medium text-royalblue-600">${{ number_format($shipping->total, 2) }}</span></p>
                    <p class="text-sm text-gray-700">Tax: <span class="font-medium text-red-600">0%</span></p>
                    <p class="text-lg font-bold text-red-600">Total: <span class="text-royalblue-700">${{ number_format($shipping->total, 2) }}</span></p>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p class="text-royalblue-600 font-medium">Thank you for shipping with Rose Door To Door Packaging And Shipping Company!</p>
            </div>
        </div>
    </div>
</x-layouts.app>