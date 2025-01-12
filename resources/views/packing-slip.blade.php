<x-layouts.app>
    <!-- Header -->
    <div class="max-w-4xl p-6 mx-auto bg-white rounded-md shadow-md">
        <h1 class="mb-2 text-2xl font-bold text-center">Packing List Form</h1>
        <p class="text-lg font-semibold text-center">Rose Door to Door Shipping and Delivery</p>
        <p class="text-sm text-center">[Contact addresses and numbers]</p>
    </div>

    <!-- Packing List Section -->
    <div class="max-w-4xl p-6 mx-auto mt-6 bg-white rounded-md shadow-md">
        <h2 class="mb-4 text-xl font-bold">Packing List</h2>

        <div class="flex justify-between">

        <p class="text-sm">
            <strong>Date:</strong> .....................................................
        </p>
        <p class="text-sm">
            <strong>Ref No.:</strong> .................................................
        </p>
        <p class="text-sm">
            <strong>Booking No.:</strong> .............................................
        </p>

        </div>

        <p class="mt-4 text-sm">
            <strong>Means:</strong> Sea, Air, Domestic, RORO Car, 20/40 Ft Container
        </p>

        <h3 class="mt-6 mb-2 text-lg font-semibold">Shipping Details</h3>
        <x-filament-tables::container>
        <x-filament-tables::table>
            <x-slot:header>
                <x-filament-tables::header-cell>
                   From: ...
                </x-filament-tables::header-cell>

                <x-filament-tables::header-cell>
                    To: ...
                </x-filament-tables::header-cell>
            </x-slot:header>

                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                       Full Name: {{ $shipping->client?->name }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        Full Name: {{ $shipping->receiver_name }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>


                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                        Address: {{ $shipping->client?->nama }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        Address: {{ $shipping->address }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>


                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                        City: {{ $shipping->client?->mcity?->name }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        City: {{ $shipping->mcity?->name }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>


                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                        State/Region: {{ $shipping->client?->mstate?->name }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        State/Region: {{ $shipping->mstate?->name }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>


                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                        Country: {{ $shipping->client?->mcountry?->name }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        Country: {{ $shipping->mcountry?->name }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>



                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                        Phone number: {{ $shipping->client?->phone }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        Phone number: {{ $shipping->receiver_phone }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>


                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                        Email: {{ $shipping->client?->email }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        Email: {{ $shipping->receiver_email }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>


                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                        National ID Type: {{ $shipping->client?->id_type }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        National ID Type: {{ $shipping->receiver_id_type }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>


                <x-filament-tables::row>
                    <x-filament-tables::cell class="p-1">
                        ID Number: {{ $shipping->client?->id_number }}
                    </x-filament-tables::cell>

                    <x-filament-tables::cell class="p-1">
                        ID Number: {{ $shipping->receiver_id_number }}
                    </x-filament-tables::cell>
                </x-filament-tables::row>

        </x-filament-tables::table>
    </x-filament-tables::container>

    <x-filament-tables::container class="mt-4">
        <x-filament-tables::table>
            <x-slot:header>
                <x-filament-tables::header-cell>
                   Container no
                </x-filament-tables::header-cell>

                <x-filament-tables::header-cell>
                    Product
                </x-filament-tables::header-cell>

                <x-filament-tables::header-cell>
                    Value
                </x-filament-tables::header-cell>
            </x-slot:header>

            @foreach ($shipping->items as $item)

            <x-filament-tables::row>
                <x-filament-tables::cell class="p-1">
                    {{ $item->box_no }}
                </x-filament-tables::cell>

                <x-filament-tables::cell class="p-1">
                    {{ $item->product?->name }} ({{ $item->quantity }})
                </x-filament-tables::cell>

                <x-filament-tables::cell class="p-1">
                   ${{ number_format($item->item_cost,2) }}
                </x-filament-tables::cell>
            </x-filament-tables::row>

            @endforeach

        </x-filament-tables::table>
    </x-filament-tables::container>

    </div>
</x-layouts.app>
