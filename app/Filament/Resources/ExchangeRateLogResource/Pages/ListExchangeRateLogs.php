<?php

namespace App\Filament\Resources\ExchangeRateLogResource\Pages;

use App\Filament\Resources\ExchangeRateLogResource;
use App\Service\ExchangeRateService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListExchangeRateLogs extends ListRecords
{
    protected static string $resource = ExchangeRateLogResource::class;

    public function getTitle(): string
    {
        return 'Exchange Rate Management';
    }

    public function getSubheading(): ?string
    {
        $service = app(ExchangeRateService::class);
        $currentRate = $service->getCurrentRate('USD', 'GHS');

        return "Current USD to GHS Rate: {$currentRate} | Use 'Quick Update' to change today's rate";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_update_usd_ghs')
                ->label('Quick Update USD → GHS')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('rate')
                        ->label('New Exchange Rate (USD to GHS)')
                        ->numeric()
                        ->required()
                        ->minValue(0.0001)
                        ->step(0.0001)
                        ->default(function () {
                            $service = app(ExchangeRateService::class);
                            return $service->getCurrentRate('USD', 'GHS');
                        })
                        ->helperText('Current rate is shown as default. Enter new rate.')
                        ->prefix('1 USD =')
                        ->suffix('GHS')
                        ->autofocus(),

                    Forms\Components\Select::make('source')
                        ->label('Source')
                        ->options([
                            'manual' => 'Manual Entry',
                            'bank' => 'Bank Rate',
                            'official' => 'Official Rate',
                        ])
                        ->default('manual')
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $service = app(ExchangeRateService::class);
                    $service->setTodayRate(
                        $data['rate'],
                        'USD',
                        'GHS',
                        auth()->id()
                    );

                    // Update the source
                    \App\Models\ExchangeRateLog::where('from_currency', 'USD')
                        ->where('to_currency', 'GHS')
                        ->whereDate('rate_date', today())
                        ->update(['source' => $data['source']]);

                    Notification::make()
                        ->title('Exchange Rate Updated')
                        ->body("USD to GHS rate set to {$data['rate']} for today")
                        ->success()
                        ->send();

                    // Redirect to refresh the page and show updated rate
                    return redirect()->route('filament.admin.resources.exchange-rate-logs.index');
                }),

            Actions\CreateAction::make()
                ->label('Add New Rate')
                ->icon('heroicon-o-plus'),
        ];
    }
}
