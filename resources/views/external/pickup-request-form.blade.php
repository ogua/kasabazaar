<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Request a Pickup - {{ config('group.company.name') }}</title>
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#A0043C">
    <link rel="icon" href="/images/favicon-32x32.png">
    <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <style>
        .field { width: 100%; border: 1px solid #d1d5db; border-radius: .5rem; padding: .55rem .75rem; }
        .field:focus { outline: none; border-color: #A0043C; box-shadow: 0 0 0 2px rgba(160, 4, 60, .2); }
        .field-error { border-color: #dc2626; }
        .ts-wrapper.single .ts-control { border: 1px solid #d1d5db; border-radius: .5rem; padding: .55rem .75rem; box-shadow: none; }
        .ts-wrapper.focus .ts-control { border-color: #A0043C; box-shadow: 0 0 0 2px rgba(160, 4, 60, .2); }
        .ts-dropdown .option .product-meta { color: #6b7280; font-size: .75rem; }
        .ts-dropdown .active { background: #fdf2f6; color: #111827; }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 flex items-center justify-between gap-4">
            <div>
                @if (file_exists(public_path('images/kasarose-logo.png')))
                    <img src="{{ asset('images/kasarose-logo.png') }}" alt="{{ config('group.company.name') }}" class="h-14">
                @else
                    <h1 class="text-2xl font-bold text-red-700">{{ config('group.company.name') }}</h1>
                @endif
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-gray-800">Pickup Request</p>
                @if ($link->expires_at)
                    <p class="text-xs text-gray-500">Link valid until {{ $link->expires_at->format('M d, Y g:i A') }}</p>
                @endif
            </div>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <h2 class="text-lg font-semibold text-red-900 mb-1">
                {{ $link->client ? 'Hello, '.$link->client->name.'!' : 'Welcome!' }}
            </h2>
            <p class="text-red-800 text-sm">
                Tell us where to collect your items, who they are going to and what you are sending.
                Our team will review your request and contact you to confirm the pickup and the shipping cost.
            </p>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-300 text-red-800 rounded-lg p-4 mb-6">
                <p class="font-semibold mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm">
                    @foreach (collect($errors->all())->unique() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('public-pickup-request.store', $link->token) }}" method="POST" id="pickupForm">
            @csrf

            @unless ($link->client_id)
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Your Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" id="sender_name" name="sender_name" value="{{ old('sender_name') }}" required maxlength="255"
                                class="field @error('sender_name') field-error @enderror">
                        </div>
                        <div>
                            <label for="sender_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                            <input type="tel" id="sender_phone" name="sender_phone" value="{{ old('sender_phone') }}" required maxlength="30"
                                placeholder="+233 20 000 0000" class="field @error('sender_phone') field-error @enderror">
                        </div>
                        <div>
                            <label for="sender_email" class="block text-sm font-medium text-gray-700 mb-1">Email (optional)</label>
                            <input type="email" id="sender_email" name="sender_email" value="{{ old('sender_email') }}" maxlength="255"
                                class="field @error('sender_email') field-error @enderror">
                        </div>
                    </div>
                </div>
            @endunless

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Pickup Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pickup_location" class="block text-sm font-medium text-gray-700 mb-1">Pickup Address *</label>
                        <input type="text" id="pickup_location" name="pickup_location" required maxlength="255"
                            value="{{ old('pickup_location', $link->client?->address) }}"
                            placeholder="Street, area, city"
                            class="field @error('pickup_location') field-error @enderror">
                    </div>
                    <div>
                        <label for="preferred_pickup_at" class="block text-sm font-medium text-gray-700 mb-1">Preferred Date &amp; Time *</label>
                        <input type="datetime-local" id="preferred_pickup_at" name="preferred_pickup_at" required
                            value="{{ old('preferred_pickup_at') }}"
                            min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                            class="field @error('preferred_pickup_at') field-error @enderror">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-1">Receivers &amp; Items</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Choose each item from our product list. If you can't find it, use <strong>+ New product</strong> and type its name.
                    Item values are set from our price list; new products are priced by our team.
                </p>
                <div id="receivers" class="flex flex-col gap-4"></div>
                <button type="button" onclick="addReceiver()" class="mt-4 w-full border-2 border-dashed border-gray-300 text-gray-700 font-medium py-3 rounded-lg hover:border-[#A0043C] hover:text-[#A0043C] transition">
                    + Add Another Receiver
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <label for="notes" class="block text-xl font-bold text-gray-800 mb-4">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000" class="field"
                    placeholder="Gate code, landmarks, fragile items, anything we should know...">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="submitBtn" class="bg-[#A0043C] text-white px-8 py-3 rounded-lg text-lg font-semibold hover:opacity-90 transition disabled:opacity-60">
                    Submit Pickup Request
                </button>
            </div>
        </form>

        <p class="text-center text-xs text-gray-500 mt-8">
            &copy; {{ date('Y') }} {{ config('group.company.name') }}. This link is personal to you &mdash; please don't share it.
        </p>
    </div>

    <datalist id="countries">
        @foreach ($countries as $country)
            <option value="{{ $country }}"></option>
        @endforeach
    </datalist>

    <template id="receiver-template">
        <div class="receiver bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-gray-700">Receiver <span class="receiver-number"></span></h4>
                <button type="button" class="remove-receiver text-sm text-red-600 hover:text-red-800">Remove receiver</button>
            </div>
            @if (count($previousReceivers))
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Send to someone you've shipped to before?</label>
                    <select class="previous-receiver field bg-white">
                        <option value="">Choose a previous receiver, or fill in a new one below</option>
                        @foreach ($previousReceivers as $index => $previous)
                            <option value="{{ $index }}">{{ $previous['name'] }}{{ $previous['phone'] ? ' ('.$previous['phone'].')' : '' }}{{ $previous['city'] ? ' — '.$previous['city'] : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="receiver-fields grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                <input type="text" data-name="name" placeholder="Full name *" required maxlength="255" class="field">
                <input type="tel" data-name="phone" placeholder="Phone *" required maxlength="30" class="field">
                <input type="text" data-name="country" placeholder="Country" list="countries" maxlength="100" class="field">
                <input type="text" data-name="state_region" placeholder="State / Region" maxlength="100" class="field">
                <input type="text" data-name="city" placeholder="City" maxlength="100" class="field">
                <input type="text" data-name="address" placeholder="Delivery address" maxlength="500" class="field">
            </div>
            <p class="text-sm font-semibold text-gray-600 mb-2">Items</p>
            <div class="items flex flex-col gap-3"></div>
            <button type="button" class="add-item mt-3 text-sm text-[#A0043C] font-semibold hover:underline">+ Add Item</button>
        </div>
    </template>

    {{-- Rows are "pickup-item", never "item": Tom Select marks each chosen product with
         class "item", and counting those as rows broke the numbering and dropped items. --}}
    <template id="item-template">
        <div class="pickup-item bg-white border border-gray-200 rounded-lg p-3">
            <div class="grid grid-cols-12 gap-2 items-end">
                <div class="col-span-12 md:col-span-6">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-medium text-gray-600">Product *</label>
                        <button type="button" class="toggle-new-product text-xs text-[#A0043C] font-medium hover:underline">+ New product</button>
                    </div>
                    <div class="product-picker">
                        <select data-name="product_id" class="product-select" placeholder="Type to search products..."></select>
                    </div>
                    <input type="text" data-name="description" placeholder="New product name *" maxlength="255" class="new-product-name field hidden" disabled>
                </div>
                <div class="col-span-4 md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Qty *</label>
                    <input type="number" data-name="quantity" required min="1" value="1" class="field">
                </div>
                <div class="col-span-6 md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Value (USD)</label>
                    <input type="text" class="item-value field bg-gray-100 text-gray-500 cursor-not-allowed" disabled placeholder="—" title="Set from our price list">
                </div>
                <div class="col-span-2 md:col-span-1 text-right">
                    <button type="button" class="remove-item text-red-600 hover:text-red-800 text-2xl leading-none" title="Remove item">&times;</button>
                </div>
                <div class="col-span-12">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                    <input type="text" data-name="details" maxlength="500" placeholder="Size, colour, condition, what's in the box..." class="field">
                </div>
            </div>
        </div>
    </template>

    <script>
        const productsUrl = @json(route('public-pickup-request.products', $link->token));
        const selectedProducts = @json($selectedProducts);
        const previousReceivers = @json($previousReceivers);
        const receiversEl = document.getElementById('receivers');
        const receiverTpl = document.getElementById('receiver-template');
        const itemTpl = document.getElementById('item-template');

        const escapeHtml = (text) => String(text ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function renumber() {
            receiversEl.querySelectorAll('.receiver').forEach((receiver, r) => {
                receiver.querySelector('.receiver-number').textContent = r + 1;
                receiver.querySelector('.remove-receiver').classList.toggle('invisible', receiversEl.children.length === 1);

                receiver.querySelectorAll('.receiver-fields [data-name]').forEach(input => {
                    input.name = `receivers[${r}][${input.dataset.name}]`;
                });

                const items = receiver.querySelectorAll('.pickup-item');
                items.forEach((item, i) => {
                    item.querySelectorAll('[data-name]').forEach(input => {
                        input.name = `receivers[${r}][items][${i}][${input.dataset.name}]`;
                    });
                    item.querySelector('.remove-item').classList.toggle('invisible', items.length === 1);
                });
            });
        }

        function showValue(item) {
            const ts = item.productPicker;
            const isNew = item.dataset.newProduct === '1';
            const product = !isNew && ts.getValue() ? ts.options[ts.getValue()] : null;
            const valueInput = item.querySelector('.item-value');

            valueInput.value = product ? product.value : '';
            valueInput.placeholder = isNew ? 'Priced by our team' : '—';
        }

        function setNewProductMode(item, isNew) {
            const ts = item.productPicker;
            const nameInput = item.querySelector('.new-product-name');

            item.dataset.newProduct = isNew ? '1' : '0';
            item.querySelector('.product-picker').classList.toggle('hidden', isNew);
            item.querySelector('.toggle-new-product').textContent = isNew ? 'Choose from list' : '+ New product';
            nameInput.classList.toggle('hidden', !isNew);
            nameInput.disabled = !isNew;
            nameInput.required = isNew;

            if (isNew) {
                ts.clear(true);
                ts.disable();
                nameInput.focus();
            } else {
                ts.enable();
                nameInput.value = '';
            }

            showValue(item);
        }

        function makeProductPicker(item) {
            const ts = new TomSelect(item.querySelector('.product-select'), {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'category'],
                options: selectedProducts,
                maxOptions: 50,
                preload: 'focus',
                loadThrottle: 250,
                load(query, callback) {
                    fetch(`${productsUrl}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } })
                        .then(response => response.json())
                        .then(callback)
                        .catch(() => callback());
                },
                render: {
                    option: (product) => `<div><div>${escapeHtml(product.name)}</div><div class="product-meta">${escapeHtml(product.category ?? '')} · USD ${escapeHtml(product.value)}</div></div>`,
                    item: (product) => `<div>${escapeHtml(product.name)}</div>`,
                    no_results: () => '<div class="no-results">No match — use "+ New product" to add it.</div>',
                },
                onChange: () => showValue(item),
            });

            item.productPicker = ts;
        }

        function addItem(receiver, values = null) {
            const item = itemTpl.content.firstElementChild.cloneNode(true);
            receiver.querySelector('.items').appendChild(item);
            makeProductPicker(item);

            item.querySelector('.toggle-new-product').addEventListener('click', () => {
                setNewProductMode(item, item.dataset.newProduct !== '1');
            });
            item.querySelector('.remove-item').addEventListener('click', () => {
                item.productPicker.destroy();
                item.remove();
                renumber();
            });

            const isNew = Boolean(values && !values.product_id && values.description);
            setNewProductMode(item, isNew);

            if (values) {
                item.querySelector('[data-name="quantity"]').value = values.quantity ?? 1;
                item.querySelector('[data-name="details"]').value = values.details ?? '';
                if (isNew) {
                    item.querySelector('.new-product-name').value = values.description;
                } else if (values.product_id) {
                    item.productPicker.setValue(values.product_id, true);
                    showValue(item);
                }
            }

            renumber();
        }

        function fillReceiver(receiver, values) {
            receiver.querySelectorAll('.receiver-fields [data-name]').forEach(input => {
                const value = values?.[input.dataset.name];
                input.value = value !== undefined && value !== null ? value : '';
            });
        }

        function addReceiver(values = null) {
            const receiver = receiverTpl.content.firstElementChild.cloneNode(true);
            if (values) {
                fillReceiver(receiver, values);
            }

            receiver.querySelector('.previous-receiver')?.addEventListener('change', (event) => {
                if (event.target.value !== '') {
                    fillReceiver(receiver, previousReceivers[event.target.value]);
                }
            });
            receiver.querySelector('.remove-receiver').addEventListener('click', () => {
                receiver.querySelectorAll('.pickup-item').forEach(item => item.productPicker.destroy());
                receiver.remove();
                renumber();
            });
            receiver.querySelector('.add-item').addEventListener('click', () => addItem(receiver));
            receiversEl.appendChild(receiver);

            const items = values?.items ? Object.values(values.items) : [null];
            items.forEach(item => addItem(receiver, item));
        }

        const previous = @json(old('receivers', []));
        const previousList = Array.isArray(previous) ? previous : Object.values(previous);
        if (previousList.length) {
            previousList.forEach(receiver => addReceiver(receiver));
        } else {
            addReceiver();
        }
        window.scrollTo(0, 0);

        document.getElementById('pickupForm').addEventListener('submit', (event) => {
            const missing = [...document.querySelectorAll('.pickup-item')].find(item => item.dataset.newProduct !== '1' && !item.productPicker.getValue());
            if (missing) {
                event.preventDefault();
                missing.productPicker.focus();
                alert('Please choose a product for every item, or use "+ New product".');
                return;
            }

            const button = document.getElementById('submitBtn');
            button.disabled = true;
            button.textContent = 'Submitting...';
        });
    </script>
</body>

</html>
