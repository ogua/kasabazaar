<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use App\Filament\Resources\ChartOfAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChartOfAccount extends EditRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete: an account with keyed prior-year balances or a live statement
            // mapping cannot be removed without silently dropping figures from an
            // already-issued statement. Deactivate it instead.
            Actions\Action::make('deactivate')
                ->label('Deactivate')
                ->icon('heroicon-o-eye-slash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('The account stops appearing on statements and in prior-year entry. Existing balances are kept.')
                ->visible(fn () => $this->record->is_active)
                ->action(fn () => $this->record->update(['is_active' => false])),

            Actions\Action::make('reactivate')
                ->label('Reactivate')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->visible(fn () => ! $this->record->is_active)
                ->action(fn () => $this->record->update(['is_active' => true])),
        ];
    }
}
