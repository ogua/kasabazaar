<?php

namespace App\Filament\Resources;

use App\Enums\InvestmentWebhookEventStatus;
use App\Filament\Resources\InvestmentWebhookEventResource\Pages;
use App\Models\InvestmentWebhookEvent;
use App\Service\InvestmentPaymentService;
use App\Service\InvestmentTransferService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentWebhookEventResource extends Resource
{
    protected static ?string $model = InvestmentWebhookEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Webhook Events';

    protected static ?int $navigationSort = 4;

    protected static bool $isScopedToTenant = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('gateway')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('event_type')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('investment.reference')
                    ->label('Investment')
                    ->placeholder('—')
                    ->url(fn (InvestmentWebhookEvent $record) => $record->investment
                        ? \App\Filament\Resources\InvestmentResource::getUrl('edit', ['record' => $record->investment])
                        : null),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(60)
                    ->tooltip(fn (InvestmentWebhookEvent $record) => $record->error_message)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gateway')
                    ->options([
                        'paystack' => 'Paystack',
                        'stripe' => 'Stripe',
                        'paystack_transfer' => 'Paystack Transfer',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'processed' => 'Processed',
                        'ignored' => 'Ignored',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('payload')
                            ->label('Raw Payload')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->rows(20)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (InvestmentWebhookEvent $record) => $record->status === InvestmentWebhookEventStatus::failed
                        && filled($record->reference))
                    ->requiresConfirmation()
                    ->action(function (InvestmentWebhookEvent $record) {
                        try {
                            match ($record->gateway) {
                                'paystack' => app(InvestmentPaymentService::class)->verifyAndRecordPaystack($record->reference),
                                'stripe' => app(InvestmentPaymentService::class)->verifyAndRecordStripe($record->reference),
                                'paystack_transfer' => match ($record->event_type) {
                                    'transfer.success' => app(InvestmentTransferService::class)->handleTransferSuccess($record->reference),
                                    'transfer.failed', 'transfer.reversed' => app(InvestmentTransferService::class)->handleTransferFailed($record->reference),
                                    default => throw new \RuntimeException("Unsupported event type for retry: {$record->event_type}"),
                                },
                                default => throw new \RuntimeException("Unsupported gateway for retry: {$record->gateway}"),
                            };

                            $record->update([
                                'status' => InvestmentWebhookEventStatus::processed,
                                'error_message' => null,
                            ]);

                            Notification::make()
                                ->title('Webhook event reprocessed successfully.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            $record->update(['error_message' => $e->getMessage()]);

                            Notification::make()
                                ->title('Retry failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentWebhookEvents::route('/'),
        ];
    }
}
