<x-layouts.app>
    <div class="relative bg-gray-100 py-10">

        <!-- Background Watermark Image -->
        <div class="absolute flex justify-center items-center pointer-events-none"
            style="top: 50%; left: 0; right: 0; bottom: 50%;">
            <!--<img src="{{ asset('images/backgrounds/Vision-Kasa-Bazaar-scaled-1.jpg') }}" alt="Logo Watermark" style="opacity: 0.2;">-->
        </div>

        <div class=" max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            <!-- Header -->
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <div>
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Watermark" style="width: 300px;">
                    <!--<h1 class="text-2xl font-bold text-gray-700">Rose Door To Door Packaging And Shipping Company</h1>-->
                    <!--<p class="text-sm text-gray-500">Shipping Invoice</p>-->
                </div>
                <div>
                    <p class="text-sm text-gray-500"><span class="font-medium">Shipping Invoice</span></p>
                    <p class="text-sm text-gray-500">Date: <span class="font-medium">{{ $shipping->created_at }}</span>
                    </p>
                    <p class="text-sm text-gray-500">Reference #: <span
                            class="font-medium">{{ $shipping->shipping_reference }}</span></p>
                </div>
            </div>

            <!-- Branch Details -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700 border-b">Client Details</h2>
                <p class="text-sm text-gray-500">Name: <span class="font-medium">{{ $shipping->client?->name }}</span>
                </p>
                <p class="text-sm text-gray-500">Address: <span
                        class="font-medium">{{ $shipping->branch?->address }}</span></p>
                <p class="text-sm text-gray-500">Phone: <span class="font-medium">{{ $shipping->client?->phone }}</span>
                </p>
                <p class="text-sm text-gray-500">Email: <span class="font-medium">{{ $shipping->client?->email }}</span>
                </p>
            </div>

            <!-- Receivers -->
            <div class="mb-6">
                <div class="space-y-4">
                    <!-- Loop through receivers -->
                    @foreach ($shipping->receivers as $receiver)
                        <div class="p-4 border rounded-lg bg-gray-50" style="background-color: rgba(1, 0, 102, 0.5)">
                            <!--<h2 class="text-lg font-semibold text-white">Receiver {{ $loop->iteration }} </h2>-->
                            <!--<hr>-->

                            @if ($receiver->receiver_name)
                                <p class="text-sm text-white">Name: <span
                                        class="font-medium">{{ $receiver->receiver_name }}</span></p>
                            @endif

                            @if ($receiver->mcountry?->name)
                                <p class="text-sm text-white">Address: <span
                                        class="font-medium">{{ $receiver->mcountry?->name }},
                                        {{ $receiver->mstate?->name }}, {{ $receiver->mcity?->name }}</span></p>
                            @endif

                            @if ($receiver->receiver_phone)
                                <p class="text-sm text-white">Phone: <span
                                        class="font-medium">{{ $receiver->receiver_phone }}</span></p>
                            @endif

                            @if ($receiver->receiver_id_type)
                                <p class="text-sm text-white">ID: <span
                                        class="font-medium">{{ $receiver->receiver_id_type }} :
                                        {{ $receiver->receiver_id_number }}</span></p>
                            @endif


                            <!-- Items -->
                            <div class="mt-4">
                                <!--<h2 class="text-lg font-semibold text-gray-700">Shipment Items</h2>-->
                                <table class="w-full mt-4 text-left border">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="p-2 border text-sm font-medium text-gray-600">#</th>
                                            <th class="p-2 border text-sm font-medium text-gray-600">Item</th>
                                            <th class="p-2 border text-sm font-medium text-gray-600">Box No</th>
                                            <th class="p-2 border text-sm font-medium text-gray-600">Quantity</th>
                                            {{-- <th class="p-2 border text-sm font-medium text-gray-600">Value</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Loop through items -->
                                        @php
                                            $totalQuantity = 0;
                                        @endphp
                                        @foreach ($receiver->items as $item)
                                            @php
                                                $totalQuantity += $item->quantity; // Sum up the total quantity
                                            @endphp
                                            <tr>
                                                <td class="p-2 border text-sm text-white">{{ $loop->iteration }}</td>
                                                <td class="p-2 border text-sm text-white">{{ $item->product?->name }}
                                                </td>
                                                <td class="p-2 border text-sm text-white">{{ $item->box_no }}</td>
                                                <td class="p-2 border text-sm text-white">{{ $item->quantity }}</td>
                                                {{-- <td class="p-2 border text-sm text-white">{{ $item->item_cost }}</td> --}}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <!-- Display Total Quantity -->

                                {{-- <div class="mt-4 text-right">
                                <p class="text-sm font-bold text-white">Total Quantity: <span
                                        class="text-blue-500">{{ $totalQuantity }}</span></p>
                            </div> --}}
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Total Amount -->
            <div class="mt-6 flex justify-end">
                <div>
                    <p class="text-sm text-gray-500">Subtotal: <span
                            class="font-medium">${{ number_format($shipping->total, 2) }}</span></p>
                    <p class="text-sm text-gray-500">Tax: <span class="font-medium">0%</span></p>
                    <p class="text-lg font-bold text-gray-700">Total: <span class=""
                            style="color:  rgba(1, 0, 102, 0.5);">${{ number_format($shipping->total, 2) }}</span></p>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>Thank you for shipping with Rose Door To Door Packaging And Shipping Company!</p>
            </div>
        </div>
    </div>
</x-layouts.app>