<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Complete Your Shipment - Rose Door To Door Shipping</title>
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#1e40af">
    <link rel="icon" href="/images/kasabazaar-logo.png">
    <link rel="apple-touch-icon" href="/images/kasabazaar-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .receiver-card {
            transition: all 0.3s ease;
        }
        .receiver-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    @if (file_exists(public_path('images/kasabazaar-logo.png')))
                        <img src="{{ asset('images/kasabazaar-logo.png') }}" alt="Logo" class="h-16">
                    @else
                        <h1 class="text-2xl font-bold text-red-600">Rose Door To Door Shipping</h1>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Shipment Reference</p>
                    <p class="text-lg font-bold text-blue-600">{{ $shipment->shipping_reference }}</p>
                    <p class="text-sm text-gray-500">Tracking: {{ $shipment->tracking_number }}</p>
                </div>
            </div>
        </div>

        <!-- Client Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold text-blue-800 mb-2">Hello, {{ $shipment->client?->name }}!</h2>
            <p class="text-blue-700">Please complete the shipment details below. Add your receivers and the items you want to ship to each of them.</p>
            <div class="mt-2 text-sm text-blue-600">
                <strong>Route:</strong> {{ $shipment->origin_branch_id }} &rarr; {{ $shipment->destination_branch_id }}
            </div>
        </div>

        <!-- Form -->
        <form action="{{ route('external-shipment-submit', $shipment->external_token) }}" method="POST" id="shipmentForm">
            @csrf

            <!-- Receivers Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Receivers & Items</h3>
                    <button type="button" onclick="addReceiver()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        + Add Receiver
                    </button>
                </div>

                <div id="receivers-container">
                    <!-- Receiver template will be added here -->
                </div>
            </div>

            <!-- Insurance Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Insurance</h3>
                <div class="flex items-start space-x-3">
                    <input type="checkbox" name="insurance_accepted" id="insurance_accepted" value="1" class="mt-1 h-5 w-5 text-blue-600 rounded">
                    <div>
                        <label for="insurance_accepted" class="font-medium text-gray-800">I want my items to be insured</label>
                        <p class="text-sm text-gray-500">Insurance provides coverage for damage or loss during shipping. Without insurance, we cannot guarantee full compensation.</p>
                    </div>
                </div>
            </div>

            <!-- Special Notes -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Special Notes</h3>
                <textarea name="client_note" rows="4" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Any special instructions or notes for your shipment..."></textarea>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-red-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-red-700 transition">
                    Submit Shipment Details
                </button>
            </div>
        </form>
    </div>

    <!-- Receiver Template -->
    <template id="receiver-template">
        <div class="receiver-card bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-700">Receiver <span class="receiver-number"></span></h4>
                <button type="button" onclick="removeReceiver(this)" class="text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="receivers[INDEX][receiver_name]" required class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="receivers[INDEX][receiver_phone]" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="receivers[INDEX][receiver_email]" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <select name="receivers[INDEX][country]" onchange="loadStates(this)" class="country-select w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Country</option>
                        @foreach ($countries as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State/Region</label>
                    <select name="receivers[INDEX][state_region]" onchange="loadCities(this)" class="state-select w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select State</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <select name="receivers[INDEX][city]" class="city-select w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select City</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="receivers[INDEX][address]" rows="2" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <!-- Items for this receiver -->
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h5 class="font-semibold text-gray-700">Items for this Receiver</h5>
                    <button type="button" onclick="addItem(this)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        + Add Item
                    </button>
                </div>
                <div class="items-container space-y-2">
                    <!-- Items will be added here -->
                </div>
            </div>
        </div>
    </template>

    <!-- Item Template -->
    <template id="item-template">
        <div class="item-row flex items-center gap-2 bg-gray-50 p-2 rounded">
            <input type="text" name="receivers[RECEIVER_INDEX][items][ITEM_INDEX][description]" placeholder="Item description" required class="flex-1 border border-gray-300 rounded p-2 text-sm">
            <input type="number" name="receivers[RECEIVER_INDEX][items][ITEM_INDEX][quantity]" placeholder="Qty" min="1" required class="w-20 border border-gray-300 rounded p-2 text-sm text-center">
            <input type="number" name="receivers[RECEIVER_INDEX][items][ITEM_INDEX][value]" placeholder="Value ($)" min="0" step="0.01" required class="w-28 border border-gray-300 rounded p-2 text-sm">
            <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </template>

    <script>
        let receiverCount = 0;

        function addReceiver() {
            const template = document.getElementById('receiver-template');
            const container = document.getElementById('receivers-container');
            const clone = template.content.cloneNode(true);

            // Update index placeholders
            clone.querySelectorAll('[name*="INDEX"]').forEach(el => {
                el.name = el.name.replace(/INDEX/g, receiverCount);
            });

            clone.querySelector('.receiver-number').textContent = receiverCount + 1;

            container.appendChild(clone);

            // Add first item automatically
            const lastReceiver = container.lastElementChild;
            addItem(lastReceiver.querySelector('.items-container').parentElement.querySelector('button'));

            receiverCount++;
            updateReceiverNumbers();
        }

        function removeReceiver(button) {
            const receiverCard = button.closest('.receiver-card');
            receiverCard.remove();
            updateReceiverNumbers();
        }

        function updateReceiverNumbers() {
            document.querySelectorAll('.receiver-number').forEach((el, index) => {
                el.textContent = index + 1;
            });
        }

        function addItem(button) {
            const receiverCard = button.closest('.receiver-card');
            const itemsContainer = receiverCard.querySelector('.items-container');
            const receiverIndex = Array.from(document.querySelectorAll('.receiver-card')).indexOf(receiverCard);
            const itemIndex = itemsContainer.children.length;

            const template = document.getElementById('item-template');
            const clone = template.content.cloneNode(true);

            clone.querySelectorAll('[name*="RECEIVER_INDEX"]').forEach(el => {
                el.name = el.name.replace(/RECEIVER_INDEX/g, receiverIndex);
            });
            clone.querySelectorAll('[name*="ITEM_INDEX"]').forEach(el => {
                el.name = el.name.replace(/ITEM_INDEX/g, itemIndex);
            });

            itemsContainer.appendChild(clone);
        }

        function removeItem(button) {
            const itemRow = button.closest('.item-row');
            const itemsContainer = itemRow.parentElement;

            if (itemsContainer.children.length > 1) {
                itemRow.remove();
            } else {
                alert('Each receiver must have at least one item.');
            }
        }

        async function loadStates(select) {
            const countryId = select.value;
            const receiverCard = select.closest('.receiver-card');
            const stateSelect = receiverCard.querySelector('.state-select');
            const citySelect = receiverCard.querySelector('.city-select');

            stateSelect.innerHTML = '<option value="">Loading...</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';

            if (!countryId) {
                stateSelect.innerHTML = '<option value="">Select State</option>';
                return;
            }

            try {
                const response = await fetch(`{{ route('external-shipment-states') }}?country_id=${countryId}`);
                const states = await response.json();

                stateSelect.innerHTML = '<option value="">Select State</option>';
                Object.entries(states).forEach(([id, name]) => {
                    stateSelect.innerHTML += `<option value="${id}">${name}</option>`;
                });
            } catch (error) {
                console.error('Error loading states:', error);
                stateSelect.innerHTML = '<option value="">Error loading states</option>';
            }
        }

        async function loadCities(select) {
            const stateId = select.value;
            const receiverCard = select.closest('.receiver-card');
            const citySelect = receiverCard.querySelector('.city-select');

            citySelect.innerHTML = '<option value="">Loading...</option>';

            if (!stateId) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                return;
            }

            try {
                const response = await fetch(`{{ route('external-shipment-cities') }}?state_id=${stateId}`);
                const cities = await response.json();

                citySelect.innerHTML = '<option value="">Select City</option>';
                Object.entries(cities).forEach(([id, name]) => {
                    citySelect.innerHTML += `<option value="${id}">${name}</option>`;
                });
            } catch (error) {
                console.error('Error loading cities:', error);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
        }

        // Add first receiver on page load
        document.addEventListener('DOMContentLoaded', function() {
            addReceiver();
        });
    </script>
</body>

</html>
