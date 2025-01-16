    <div class="bg-gray-100 py-10">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-700">KasaBazaar Shipping Agreement</h1>
                <p class="text-lg text-gray-500">Please read the following shipping agreement carefully before consenting
                    to the terms.</p>
            </div>

            <!-- Shipping Agreement Content -->
            <div class="space-y-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-700">Shipping Terms & Conditions</h2>
                <p class="text-sm text-gray-500">
                    By agreeing to the following terms, you consent to ship goods with KasaBazaar. Below are the
                    conditions that apply to all shipments:
                </p>
                <ul class="list-disc pl-6 space-y-2 text-sm text-gray-500">
                    <li>The shipper is responsible for ensuring the accuracy of all shipment details.</li>
                    <li>All shipments must be securely packed to avoid damage during transit.</li>
                    <li>KasaBazaar is not liable for delays caused by external factors like weather, customs, etc.</li>
                    <li>The receiver must provide valid identification upon receipt of the shipment.</li>
                    <li>Customs regulations must be adhered to for international shipments.</li>
                    <li>Any prohibited items are not allowed for shipment, and KasaBazaar reserves the right to refuse
                        such shipments.</li>
                    <li>Shipment insurance is available and must be opted for during the shipping process.</li>
                    <li>KasaBazaar reserves the right to amend these terms at any time without prior notice.</li>
                </ul>
            </div>

            <!-- Consent Form -->
            <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Agreement Consent</h3>
                <form wire:submit="save">
                    {{ $this->form }}

                    <x-filament::button
                        icon="heroicon-m-arrow-right"
                        style="background-color: #A0043C;margin-top: 20px;"
                        wire:click='create'
                    >
                       Proceed To Payment
                    </x-filament::button>
                </form>

                <x-filament-actions::modals />
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>Thank you for choosing KasaBazaar for your shipping needs!</p>
            </div>
        </div>
    </div>
