<?php

namespace App\Filament\Vendor\Pages;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\VendorApi;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Earnings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected string $view = 'filament.vendor.pages.earnings';

    public array $summary = [];

    public array $transactions = [];

    public function mount(VendorApi $vendorApi): void
    {
        $this->summary = $vendorApi->earningsSummary();
        $this->transactions = $vendorApi->earningsTransactions(['per_page' => 20])->data;
    }

    public function requestPayoutAction(): Action
    {
        return Action::make('requestPayout')
            ->label('Request Payout')
            ->schema([
                TextInput::make('amount_ghs')->label('Amount (GHS)')->numeric()->required()->minValue(1),
                Select::make('payout_method')
                    ->label('Payout Method')
                    ->options(['momo' => 'Mobile Money', 'bank' => 'Bank Transfer'])
                    ->required()
                    ->default('momo'),
                Textarea::make('payout_details')->label('Account Details')->rows(2)->required(),
            ])
            ->action(function (array $data, VendorApi $vendorApi) {
                try {
                    $vendorApi->requestPayout([
                        'amount_ghs' => (float) $data['amount_ghs'],
                        'payout_method' => $data['payout_method'],
                        'payout_details' => $data['payout_details'],
                    ]);

                    Notification::make()->title('Payout requested.')->success()->send();
                    $this->summary = $vendorApi->earningsSummary();
                } catch (KasabazaarApiException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }

    protected function getHeaderActions(): array
    {
        return [$this->requestPayoutAction()];
    }
}
