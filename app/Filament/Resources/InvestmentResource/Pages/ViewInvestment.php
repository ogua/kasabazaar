<?php

namespace App\Filament\Resources\InvestmentResource\Pages;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Actions;
use Filament\Forms\Get;
use App\Models\Investment;
use Filament\Actions\Action;
use App\Enums\InvestmentStatus;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Service\InvestmentInterestService;
use App\Filament\Resources\InvestmentResource;

class ViewInvestment extends ViewRecord
{
    protected static string $resource = InvestmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('postInterest')
                    ->label('Post Interest')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->visible(fn (Investment $record) => $record->status === InvestmentStatus::active)
                    ->form(function (Investment $record) {
                        $from = $record->last_interest_posted_through
                            ? Carbon::parse($record->last_interest_posted_through)->addDay()
                            : Carbon::parse($record->start_date);

                        return [
                            Forms\Components\DatePicker::make('period_start')
                                ->label('From')
                                ->default($from)
                                ->live()
                                ->required(),

                            Forms\Components\DatePicker::make('period_end')
                                ->label('To')
                                ->default(now())
                                ->afterOrEqual('period_start')
                                ->live()
                                ->required(),

                            Forms\Components\Placeholder::make('preview')
                                ->label('Computed Interest')
                                ->content(function (Get $get) use ($record) {
                                    if (! $get('period_start') || ! $get('period_end')) {
                                        return '—';
                                    }

                                    try {
                                        $accrual = app(InvestmentInterestService::class)->periodAccrual(
                                            $record,
                                            Carbon::parse($get('period_start')),
                                            Carbon::parse($get('period_end')),
                                            (float) $record->current_balance
                                        );
                                    } catch (\Throwable $e) {
                                        return $e->getMessage();
                                    }

                                    $lines = collect($accrual['segments'])->map(fn ($segment) => sprintf(
                                        '%d: %s%% (%s) × %d days on $%s = $%s',
                                        $segment['year'],
                                        number_format($segment['rate'], 2),
                                        $segment['rate_source'],
                                        $segment['days_held'],
                                        number_format($segment['balance_start'], 2),
                                        number_format($segment['interest'], 2)
                                    ))->implode("\n");

                                    return sprintf('Total: $%s'."\n".'%s', number_format($accrual['total_interest'], 2), $lines);
                                })
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('override_amount')
                                ->label('Override Amount (optional)')
                                ->numeric()
                                ->prefix('USD')
                                ->helperText('Only applied when the period above resolves to a single calendar-year segment.'),
                                // ->visible(function (Get $get) use ($record) {
                                //     if (! $get('period_start') || ! $get('period_end')) {
                                //         return false;
                                //     }

                                //     try {
                                //         $accrual = app(InvestmentInterestService::class)->periodAccrual(
                                //             $record,
                                //             Carbon::parse($get('period_start')),
                                //             Carbon::parse($get('period_end')),
                                //             (float) $record->current_balance
                                //         );
                                //     } catch (\Throwable $e) {
                                //         return false;
                                //     }

                                //     return count($accrual['segments']) === 1;
                                // }),
                        ];
                    })
                    ->action(function (Investment $record, array $data) {
                        $service = app(InvestmentInterestService::class);

                        try {
                            $drafts = $service->generateDraftForPeriod(
                                $record,
                                Carbon::parse($data['period_start']),
                                Carbon::parse($data['period_end'])
                            );

                            $overrides = [];
                            if (filled($data['override_amount'] ?? null) && $drafts->count() === 1) {
                                $overrides[$drafts->first()->id] = (float) $data['override_amount'];
                            }

                            $service->postDraftBatch($drafts, auth()->user(), $overrides);

                            Notification::make()
                                ->title("Interest posted for {$data['period_start']} – {$data['period_end']}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to post interest')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            Actions\EditAction::make(),
        ];
    }
}
