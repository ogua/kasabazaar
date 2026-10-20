@php
    $byStage = $media->groupBy('stage');
    $stages = \App\Models\ShipmentMedia::STAGES + $byStage->keys()
        ->mapWithKeys(fn ($stage) => [$stage => \Illuminate\Support\Str::headline((string) $stage) ?: 'Other'])
        ->all();
@endphp

@if ($media->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $emptyMessage }}</p>
@else
    <div class="flex flex-col gap-4">
        @foreach ($stages as $stage => $label)
            @continue(! $byStage->has($stage))

            <div>
                <div class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">{{ $label }}</div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($byStage[$stage] as $item)
                        <div class="rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                            @if ($item->type === 'image')
                                <a href="{{ $item->url() }}" target="_blank" rel="noopener">
                                    <img src="{{ $item->url() }}" alt="{{ $item->caption ?? $label }}" class="mb-2 h-32 w-full rounded object-cover" />
                                </a>
                            @elseif ($item->type === 'video')
                                <video src="{{ $item->url() }}" controls preload="metadata" class="mb-2 h-32 w-full rounded bg-black"></video>
                            @else
                                <a href="{{ $item->url() }}" target="_blank" rel="noopener"
                                   class="mb-2 flex h-32 w-full flex-col items-center justify-center gap-1 rounded bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    <x-filament::icon icon="heroicon-o-document-text" class="h-10 w-10" />
                                    <span class="px-2 text-center text-xs break-all">{{ $item->fileName() }}</span>
                                </a>
                            @endif

                            @if ($item->caption)
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->caption }}</p>
                            @endif
                            @if ($showMeta)
                                <p class="mt-1 text-[11px] text-gray-400">
                                    {{ $item->created_at?->format('d M Y, H:i') }}@if ($item->uploader) · {{ $item->uploader->name }}@endif
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
