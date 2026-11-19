@php
    /** @var \App\Models\Activity $activity */
    $diff = $activity->diff();
    $details = $activity->details();
    $colors = [
        'danger' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
        'success' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
        'warning' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
        'info' => 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/30',
        'gray' => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10',
    ];
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $colors[$activity->eventColor()] ?? $colors['info'] }}">
                {{ \Illuminate\Support\Str::headline($activity->event ?? 'event') }}
            </span>
            @if ($activity->risk === \App\Models\Activity::RISK_HIGH)
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $colors['danger'] }}">High risk</span>
            @endif
            @if (($showSubject ?? false) && $activity->subjectTypeLabel())
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->subjectTypeLabel() }} {{ $activity->subject_label }}</span>
            @endif
        </div>
        <time class="text-xs text-gray-500 dark:text-gray-400" datetime="{{ $activity->created_at?->toIso8601String() }}" title="{{ $activity->created_at?->format('d M Y, H:i:s') }}">
            {{ $activity->created_at?->format('d M Y, H:i') }} · {{ $activity->created_at?->diffForHumans() }}
        </time>
    </div>

    <p class="mt-2 text-sm font-medium text-gray-950 dark:text-white">{{ $activity->description }}</p>

    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        By <span class="font-medium text-gray-700 dark:text-gray-200">{{ $activity->actorLabel() }}</span>
        @if ($activity->impersonator)
            <span class="text-warning-600 dark:text-warning-400">(impersonated by {{ $activity->impersonator->name }})</span>
        @endif
        · {{ $activity->channelLabel() }}
        @if ($activity->ip_address)
            · {{ $activity->ip_address }}
        @endif
    </p>

    @if ($diff !== [])
        <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2 font-medium">Field</th>
                        <th class="px-3 py-2 font-medium">Before</th>
                        <th class="px-3 py-2 font-medium">After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($diff as $row)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2 font-medium text-gray-700 dark:text-gray-200">{{ \Illuminate\Support\Str::headline($row['field']) }}</td>
                            <td class="break-all px-3 py-2 text-gray-500 line-through decoration-danger-400/60 dark:text-gray-400">{{ \App\Models\Activity::displayValue($row['old']) }}</td>
                            <td class="break-all px-3 py-2 text-gray-900 dark:text-white">{{ \App\Models\Activity::displayValue($row['new']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($details !== [])
        <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-1 text-xs sm:grid-cols-2">
            @foreach ($details as $key => $value)
                <div class="flex gap-2">
                    <dt class="shrink-0 text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::headline((string) $key) }}:</dt>
                    <dd class="break-all text-gray-800 dark:text-gray-200">{{ \App\Models\Activity::displayValue($value) }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</div>
