@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Activity> $activities */
@endphp

<div class="space-y-4">
    @forelse ($activities as $activity)
        @include('filament.audit.partials.entry', ['activity' => $activity, 'showSubject' => true])
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
            Nothing has been recorded for this record yet. Changes made from now on will appear here.
        </div>
    @endforelse

    @if ($activities->count() >= $limit)
        <p class="text-center text-xs text-gray-500 dark:text-gray-400">
            Showing the latest {{ $limit }} entries. Open Audit &amp; Security → Activity Log for the full trail.
        </p>
    @endif
</div>
