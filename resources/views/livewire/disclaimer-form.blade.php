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
            Disclaimer Form
        </h1>


        <div class="p-4 text-left border border-dashed rounded-md bg-gray-50">
            <p class="text-left text-gray-600">
                Please read the below information before signing.  This form is required.
            </p>

<p class="text-left text-gray-600 mt-3 font-bold">Destination Charges:</p>

<p class="text-left text-gray-600">All international shipments are subject to Destination Fees: Custom Duties, Taxes, (if applicable) Handling, Terminal / Port Fees, Storage and Door Delivery which are beyond our control. These charges vary according to destination country and are accepted practices in the shipping industry.
</p>

<p class="text-left text-gray-600">
Because of tight security, U.S. Customs or TSA may require your Cargo to be inspected and there could be charges related to such action. These charges will be billed to the shipper.
</p>

<p class="text-left text-gray-600 mt-3">
Shipping charges do NOT include Insurance coverage.
</p>

<p class="text-left text-gray-600 font-bold mt-3">
Documentation:
</p>

<p class="text-left text-gray-600">
It’s the shipper’s responsibility to fill out the Packing List Form and Disclaimer Form, submit it Online, Mail or Fax.
Please note that we cannot ship out Freight without proper Documentation. </p>

<p class="text-left text-gray-600 mt-3 font-bold">
Transit Time:
</p>

<p class="text-left text-gray-600">
We cannot guarantee on time arrivals, mainly because of Weather, Security, Port delays or other problems however be assured we will make every effort to have it there on time and in good shape.
</p>

<p class="text-left text-gray-600 font-bold mt-3">
Payment:</p>

<p class="text-left text-gray-600">
The Cargo is on a prepaid basis and full payment is due upon presentation of the invoice.
Payment not received by the due date is subject to a late fee.
</p>

<p class="text-left text-gray-600">
Please read the above information before signing below.
</p>
            <div class="mt-5" style="margin-top: 20px;">
                <form wire:submit="save">
                    {{ $this->form }}

                    @php
                        $url = route('make-payment-agreement',['record' => $this->record])
                    @endphp

                    <x-filament::button
                        href="{{ $url }}"
                        tag="a"
                        icon="heroicon-m-arrow-right"
                        style="background-color: #A0043C;margin-top: 20px;"
                    >
                       Proceed
                    </x-filament::button>
                </form>

                <x-filament-actions::modals />

            </div>
        </div>
    </div>
</div>


