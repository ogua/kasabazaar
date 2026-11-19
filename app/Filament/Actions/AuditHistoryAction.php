<?php

namespace App\Filament\Actions;

use App\Models\Activity;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * "History" header action: a slide-over timeline of who changed this record (and its
 * children — a shipment's items and payments, an investment's transactions…), when, from
 * where, and what changed. Only for staff allowed to read the audit trail.
 */
class AuditHistoryAction
{
    public const LIMIT = 200;

    public static function make(string $name = 'auditHistory'): Action
    {
        return Action::make($name)
            ->label('History')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->slideOver()
            ->modalWidth('2xl')
            ->modalHeading('History')
            ->modalDescription(fn (Model $record): string => 'Every change to this record, newest first.')
            ->modalContent(fn (Model $record) => view('filament.audit.history', [
                'activities' => self::activitiesFor($record),
                'limit' => self::LIMIT,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->visible(fn (): bool => auth()->user()?->can('viewAny', Activity::class) ?? false);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    public static function activitiesFor(Model $record)
    {
        return Activity::query()
            ->forRecord($record)
            ->with(['causer', 'impersonator'])
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();
    }
}
