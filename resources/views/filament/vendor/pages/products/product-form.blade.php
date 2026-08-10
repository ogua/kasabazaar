<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Save Product
        </x-filament::button>
    </form>

    @if (isset($images))
        <x-filament::section heading="Images" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @forelse ($images as $image)
                    <img src="{{ $image['url'] }}" alt="" class="h-20 w-20 rounded object-cover">
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No images uploaded yet.</p>
                @endforelse
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
