<?php

namespace App\Filament\Resources\InvestmentResource\Pages;

use App\Filament\Resources\InvestmentResource;
use App\Models\InvestmentRateOverride;
use App\Service\InvestmentPaymentService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateInvestment extends CreateRecord
{
    protected static string $resource = InvestmentResource::class;

    protected ?float $initialAnnualRate = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialAnnualRate = filled($data['initial_annual_rate'] ?? null) ? (float) $data['initial_annual_rate'] : null;
        unset($data['initial_annual_rate']);

        $data['recorded_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->initialAnnualRate !== null) {
            InvestmentRateOverride::updateOrCreate(
                [
                    'investment_id' => $this->record->id,
                    'year' => Carbon::parse($this->record->start_date)->year,
                ],
                [
                    'annual_rate' => $this->initialAnnualRate,
                    'created_by' => auth()->id(),
                    'notes' => 'Captured at investment creation.',
                ]
            );
        }

        if ($this->record->deposit_gateway === 'manual') {
            app(InvestmentPaymentService::class)->recordManualDeposit($this->record, [
                'payment_method' => $this->record->payment_method,
                'payment_reference' => $this->record->payment_reference,
                'receipt_path' => $this->record->receipt_path,
            ]);
        }
    }
}
