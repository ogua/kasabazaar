<?php

namespace App\Filament\Investor\Resources\InvestmentResource\Pages;

use App\Filament\Investor\Resources\InvestmentResource;
use App\Models\Investment;
use App\Service\InvestmentPaymentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInvestment extends CreateRecord
{
    protected static string $resource = InvestmentResource::class;

    private string $checkoutUrl = '';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['investor_id'] = auth()->user()->investor_id;
        $data['start_date'] = now();
        $data['recorded_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Investment $investment */
        $investment = $this->record;
        $investor = $investment->investor;
        $email = $investor->email ?? auth()->user()->email;

        $viewUrl = InvestmentResource::getUrl('view', ['record' => $investment]);
        $indexUrl = InvestmentResource::getUrl('index');

        $service = app(InvestmentPaymentService::class);

        try {
            $result = $investment->deposit_gateway === 'stripe'
                ? $service->initiateStripe($investment, $email, $viewUrl, $indexUrl)
                : $service->initiatePaystack($investment, $email, $viewUrl);

            $this->checkoutUrl = $result['url'];
        } catch (\Throwable $e) {
            $investment->delete();

            Notification::make()
                ->title('Could not start checkout')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->checkoutUrl = InvestmentResource::getUrl('index');
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->checkoutUrl ?: InvestmentResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Redirecting you to secure checkout…')
            ->success();
    }
}
