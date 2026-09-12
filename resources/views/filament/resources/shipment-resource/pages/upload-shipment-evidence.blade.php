<x-filament-panels::page>
    <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Shipment <span class="font-medium text-gray-950 dark:text-white">{{ $record->shipping_reference }}</span>
        has been saved. Upload evidence photos/videos of the items now, or skip — you can always add media later.
    </div>

    <form wire:submit="upload">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="upload">
                Upload &amp; Continue
            </x-filament::button>

            <x-filament::button color="gray" wire:click="skip" wire:loading.attr="disabled" wire:target="skip">
                Skip
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
