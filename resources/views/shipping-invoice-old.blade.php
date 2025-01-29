<x-layouts.app>
    <div class="bg-gray-100 py-10">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            <!-- Header -->
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-700">KasaBazaar (Rose Door To Door)</h1>
                    <p class="text-sm text-gray-500">Shipping Invoice</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date: <span class="font-medium">{{ $shipping->created_at }}</span></p>
                    <p class="text-sm text-gray-500">Reference #: <span class="font-medium">{{ $shipping->shipping_reference }}</span></p>
                </div>
            </div>
    
            <!-- Branch Details -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700">Branch Details</h2>
                <p class="text-sm text-gray-500">Name: <span class="font-medium">{{ $shipping->branch?->name }}</span></p>
                <p class="text-sm text-gray-500">Address: <span class="font-medium">{{ $shipping->branch?->address }}</span></p>
                <p class="text-sm text-gray-500">Phone: <span class="font-medium">{{ $shipping->branch?->phone }}</span></p>
                <p class="text-sm text-gray-500">Email: <span class="font-medium">{{ $shipping->branch?->email }}</span></p>
            </div>
    
            <!-- Receivers -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700">Receivers</h2>
                <div class="space-y-4">
                    <!-- Loop through receivers -->
                    @foreach ($shipping->receivers as $receiver)
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <p class="text-sm text-gray-500">Name: <span class="font-medium">{{ $receiver->receiver_name }}</span></p>
                        <p class="text-sm text-gray-500">Address: <span class="font-medium">{{ $receiver->mcountry?->name }}, {{ $receiver->mstate?->name }}, {{ $receiver->mcity?->name }}</span></p>
                        <p class="text-sm text-gray-500">Phone: <span class="font-medium">{{ $receiver->receiver_phone }}</span></p>
                        <p class="text-sm text-gray-500">ID: <span class="font-medium">{{ $receiver->receiver_id_type }} : {{ $receiver->receiver_id_number }}</span></p>
                    </div>
                    @endforeach
                </div>
            </div>
    
            <!-- Items -->
            <div>
                <h2 class="text-lg font-semibold text-gray-700">Shipment Items</h2>
                <table class="w-full mt-4 text-left border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border text-sm font-medium text-gray-600">#</th>
                            <th class="p-2 border text-sm font-medium text-gray-600">Item</th>
                            <th class="p-2 border text-sm font-medium text-gray-600">box_no</th>
                            <th class="p-2 border text-sm font-medium text-gray-600">Quantity</th>
                            <th class="p-2 border text-sm font-medium text-gray-600">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loop through items -->
                        @foreach ($shipping->items as $item)
                        <tr>
                            <td class="p-2 border text-sm text-gray-500">{{ $loop->iteration }}</td>
                            <td class="p-2 border text-sm text-gray-500">{{ $item->product?->name }}</td>
                            <td class="p-2 border text-sm text-gray-500">{{ $item->box_no }}</td>
                            <td class="p-2 border text-sm text-gray-500">{{ $item->quantity }}</td>
                            <td class="p-2 border text-sm text-gray-500">{{ $item->item_cost }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
    
            <!-- Total Amount -->
            <div class="mt-6 flex justify-end">
                <div>
                    <p class="text-sm text-gray-500">Subtotal: <span class="font-medium">${{ number_format($shipping->total,2) }}</span></p>
                    <p class="text-sm text-gray-500">Tax: <span class="font-medium">0%</span></p>
                    <p class="text-lg font-bold text-gray-700">Total: <span class="text-blue-500">${{ number_format($shipping->total,2) }}</span></p>
                </div>
            </div>
    
            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>Thank you for shipping with KasaBazaar (Rose Door To Door)!</p>
            </div>
        </div>
    </div>
</x-layouts.app>