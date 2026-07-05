<?php

namespace App\Filament\Resources\InvestmentResource\Pages;

use App\Filament\Resources\InvestmentResource;
use App\Service\InvestmentPaymentService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvestment extends CreateRecord
{
    protected static string $resource = InvestmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->deposit_gateway === 'manual') {
            app(InvestmentPaymentService::class)->recordManualDeposit($this->record, [
                'payment_method' => $this->record->payment_method,
                'payment_reference' => $this->record->payment_reference,
                'receipt_path' => $this->record->receipt_path,
            ]);
        }
    }
}
