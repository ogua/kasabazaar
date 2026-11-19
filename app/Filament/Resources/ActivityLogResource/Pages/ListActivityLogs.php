<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Exports\ActivityLogExport;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Widgets\AuditSecurityStats;
use App\Services\Audit\Audit;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AuditSecurityStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export to Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    $query = $this->getFilteredSortedTableQuery();

                    Audit::download('Activity log export', null, ['rows' => (clone $query)->count()]);

                    return Excel::download(new ActivityLogExport($query), 'activity-log-'.now()->format('Y-m-d-His').'.xlsx');
                }),
        ];
    }
}
