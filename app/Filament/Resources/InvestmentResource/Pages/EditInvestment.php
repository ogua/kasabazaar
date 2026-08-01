<?php

namespace App\Filament\Resources\InvestmentResource\Pages;

use App\Filament\Resources\InvestmentResource;
use App\Models\InvestmentRateOverride;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestment extends EditRecord
{
    protected static string $resource = InvestmentResource::class;

    protected ?float $initialAnnualRate = null;

    protected function getHeaderActions(): array
    {
        return [
            InvestmentResource::postInterestHeaderAction(),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->initialAnnualRate = filled($data['initial_annual_rate'] ?? null) ? (float) $data['initial_annual_rate'] : null;
        unset($data['initial_annual_rate']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->initialAnnualRate === null) {
            return;
        }

        InvestmentRateOverride::updateOrCreate(
            [
                'investment_id' => $this->record->id,
                'year' => Carbon::parse($this->record->start_date)->year,
            ],
            [
                'annual_rate' => $this->initialAnnualRate,
                'created_by' => auth()->id(),
                'notes' => 'Updated via investment edit form.',
            ]
        );
    }
}
