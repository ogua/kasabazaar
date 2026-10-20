<span class="flex items-center gap-3">
    @if ($item->type === 'image')
        <img src="{{ $item->url() }}" alt="" class="h-12 w-16 shrink-0 rounded object-cover" />
    @else
        <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
            <x-filament::icon :icon="$item->type === 'video' ? 'heroicon-o-film' : 'heroicon-o-document-text'" class="h-6 w-6" />
        </span>
    @endif

    <span class="flex flex-col">
        <span>{{ $item->caption ?: $item->fileName() }}</span>
        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
            {{ \App\Models\ShipmentMedia::STAGES[$item->stage] ?? $item->stage }}
            · {{ \App\Models\ShipmentMedia::TYPES[$item->type] ?? $item->type }}
            · {{ $item->created_at?->format('d M Y, H:i') }}
        </span>
    </span>
</span>
