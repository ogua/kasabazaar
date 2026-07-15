<?php

namespace App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource\Pages;

use App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource;
use App\Models\Investment;
use App\Models\InvestmentWithdrawalRequest;
use App\Services\InvestmentWithdrawalApprovalService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInvestmentWithdrawalRequest extends CreateRecord
{
    protected static string $resource = InvestmentWithdrawalRequestResource::class;

    protected function handleRecordCreation(array $data): InvestmentWithdrawalRequest
    {
        $investment = Investment::where('id', $data['investment_id'])
            ->where('investor_id', auth()->user()->investor_id)
            ->firstOrFail();

        try {
            return app(InvestmentWithdrawalApprovalService::class)->submit($investment, $data);
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title('Unable to submit withdrawal request')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
