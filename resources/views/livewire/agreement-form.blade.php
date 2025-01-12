<div class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-2xl p-8 text-gray-900 rounded-lg shadow-lg dark:bg-gray-800 dark:text-white">

        <div class="flex items-center justify-center">
            <img
                alt="UniMAC logo"
                src="{{ asset('images/kasabazaar-logo.png') }}"
                style="height: 4rem;" class="flex items-center justify-center mb-4 text-left fi-logo"
            />

        </div>

        <h1 class="text-2xl font-bold tracking-tight text-center fi-simple-header-heading text-gray-950 dark:text-white">
            Cargo Screening and Shipping Agreement
        </h1>

        <div class="p-4 text-left border border-dashed rounded-md bg-gray-50">
            <p class="text-left text-gray-600">
                Please read the below agreement carefully before signing. This form is required for all cargo shipments.
            </p>

            <p class="text-left text-gray-600 mt-3 font-bold">Authorization for Screening:</p>
            <p class="text-left text-gray-600">
                The Customer authorizes Rose Door to Door Shipping and Delivery and its agents to screen all cargo provided by the Customer from the date of this Agreement forward until revoked in writing. Screening may include physical inspections, repackaging, and any other actions required to comply with TSA regulations.
            </p>

            <p class="text-left text-gray-600 mt-3 font-bold">Liability and Limitations:</p>
            <p class="text-left text-gray-600">
                - The Customer agrees that the Company is not liable for loss, damage, or delays resulting from the screening, physical inspection, or repackaging of cargo.
                - The Company agrees to handle all cargo in compliance with TSA regulations and take reasonable measures to protect the cargo during the screening and shipping process.
            </p>

            <p class="text-left text-gray-600 mt-3 font-bold">Customer Obligations:</p>
            <p class="text-left text-gray-600">
                - The Customer must provide accurate and truthful information regarding the contents of the cargo.
                - The Customer agrees that cargo will not be shipped if consent to screening is not granted.
                - The Customer agrees to provide identification and documentation as required by the Company or TSA regulations.
            </p>

            <p class="text-left text-gray-600 mt-3 font-bold">Consent to Repackaging:</p>
            <p class="text-left text-gray-600">
                The Customer authorizes the Company to repack cargo if necessary to complete the screening process or to ensure safe transportation.
            </p>

            <p class="text-left text-gray-600 mt-3 font-bold">Indemnification:</p>
            <p class="text-left text-gray-600">
                The Customer agrees to indemnify and hold harmless the Company, its agents, and affiliates from any claims, damages, or losses arising from non-compliance with TSA regulations or failure to disclose accurate information about the cargo.
            </p>

            <p class="text-left text-gray-600 mt-3 font-bold">Termination of Agreement:</p>
            <p class="text-left text-gray-600">
                This Agreement remains in effect until revoked in writing by the Customer. Such revocation must be submitted to the Company and will not affect any obligations arising from cargo tendered prior to the revocation.
            </p>

            <p class="text-left text-gray-600 mt-3 font-bold">Payment:</p>
            <p class="text-left text-gray-600">
                The cargo is on a prepaid basis, and full payment is due upon presentation of the invoice. Payment not received by the due date is subject to a late fee.
            </p>

            <p class="text-left text-gray-600">
                Please read the above agreement carefully before signing below.
            </p>

            <div class="mt-5" style="margin-top: 20px;">
                <form wire:submit="save">
                    {{ $this->form }}

                    <x-filament::button
                        icon="heroicon-m-arrow-right"
                        style="background-color: #A0043C;margin-top: 20px;"
                    >
                       Proceed To Payment
                    </x-filament::button>
                </form>

                <x-filament-actions::modals />

            </div>
        </div>
    </div>
</div>
