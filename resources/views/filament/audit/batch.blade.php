@php
    /** @var \App\Models\Activity $record */
    $record = $getRecord();
    $siblings = \App\Models\Activity::query()
        ->where('batch_uuid', $record->batch_uuid)
        ->whereKeyNot($record->getKey())
        ->with(['causer', 'impersonator'])
        ->orderBy('id')
        ->limit(100)
        ->get();
@endphp

<div class="space-y-3">
    @forelse ($siblings as $sibling)
        <a href="{{ \App\Filament\Resources\ActivityLogResource::getUrl('view', ['record' => $sibling]) }}" class="block hover:opacity-90">
            @include('filament.audit.partials.entry', ['activity' => $sibling, 'showSubject' => true])
        </a>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Nothing else was recorded in the same action.</p>
    @endforelse
</div>
