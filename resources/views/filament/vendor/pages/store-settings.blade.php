<x-filament-panels::page>
    <x-filament::section heading="Branding" class="mb-4">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div>
                <div class="mb-2 text-sm font-medium">Logo</div>
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" class="mb-2 h-20 w-20 rounded-full object-cover">
                @endif
            </div>
            <div>
                <div class="mb-2 text-sm font-medium">Banner</div>
                @if ($bannerUrl)
                    <img src="{{ $bannerUrl }}" class="mb-2 h-20 w-60 rounded object-cover">
                @endif
            </div>
        </div>
    </x-filament::section>

    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Save Settings
        </x-filament::button>
    </form>
</x-filament-panels::page>
