<x-filament-panels::page>
    <x-filament-widgets::widgets
        :widgets="$this->getHeaderWidgets()"
        :columns="1"
    />

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-filament::section>
            <x-slot name="heading">
                Recent Trips
            </x-slot>

            @php
                $recentTrips = \App\Models\Trip::with(['vehicle', 'driver'])
                    ->latest()
                    ->limit(5)
                    ->get();
            @endphp

            <div class="space-y-3">
                @forelse($recentTrips as $trip)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div>
                            <div class="font-medium">{{ $trip->trip_number }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $trip->vehicle?->registration_number ?? 'N/A' }} - {{ $trip->driver?->name ?? 'N/A' }}
                            </div>
                        </div>
                        <x-filament::badge :color="$trip->status->getColor()">
                            {{ $trip->status->getLabel() }}
                        </x-filament::badge>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No trips found</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Vehicles Needing Service
            </x-slot>

            @php
                $vehiclesNeedingService = \App\Models\Vehicle::where(function ($query) {
                    $query->where('next_service_due', '<=', now()->addDays(7))
                        ->orWhere('insurance_expiry', '<=', now()->addDays(30))
                        ->orWhere('roadworthy_expiry', '<=', now()->addDays(30));
                })->limit(5)->get();
            @endphp

            <div class="space-y-3">
                @forelse($vehiclesNeedingService as $vehicle)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div>
                            <div class="font-medium">{{ $vehicle->registration_number }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $vehicle->make }} {{ $vehicle->model }}
                            </div>
                        </div>
                        <div class="text-sm">
                            @if($vehicle->next_service_due && $vehicle->next_service_due <= now()->addDays(7))
                                <span class="text-red-500">Service due: {{ $vehicle->next_service_due->format('M d') }}</span>
                            @elseif($vehicle->insurance_expiry && $vehicle->insurance_expiry <= now()->addDays(30))
                                <span class="text-orange-500">Insurance: {{ $vehicle->insurance_expiry->format('M d') }}</span>
                            @elseif($vehicle->roadworthy_expiry && $vehicle->roadworthy_expiry <= now()->addDays(30))
                                <span class="text-orange-500">Roadworthy: {{ $vehicle->roadworthy_expiry->format('M d') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">All vehicles are up to date</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
