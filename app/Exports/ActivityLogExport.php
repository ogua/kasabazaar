<?php

namespace App\Exports;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * The Activity Log table, as currently filtered, for auditors.
 */
class ActivityLogExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query->with(['causer', 'impersonator', 'branch']);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['When', 'Who', 'Impersonated by', 'Event', 'Risk', 'Module', 'Record type', 'Record', 'Description', 'Changes', 'Branch', 'Channel', 'Source', 'IP address', 'URL'];
    }

    /**
     * @param  Activity  $activity
     * @return list<string|null>
     */
    public function map($activity): array
    {
        $changes = collect($activity->diff())
            ->map(fn (array $row): string => Str::headline($row['field']).': '.Activity::displayValue($row['old']).' → '.Activity::displayValue($row['new']))
            ->implode("\n");

        return [
            $activity->created_at?->format('Y-m-d H:i:s'),
            $activity->actorLabel(),
            $activity->impersonator?->name,
            Str::headline((string) $activity->event),
            $activity->risk,
            Str::headline((string) $activity->log_name),
            $activity->subjectTypeLabel(),
            $activity->subject_label,
            $activity->description,
            $changes,
            $activity->branch?->name,
            $activity->channelLabel(),
            $activity->source,
            $activity->ip_address,
            $activity->url,
        ];
    }
}
