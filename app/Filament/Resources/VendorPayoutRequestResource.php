<?php

namespace App\Filament\Resources;

use App\Enums\VendorPayoutStatus;
use App\Enums\VendorTransactionType;
use App\Filament\Resources\VendorPayoutRequestResource\Pages;
use App\Models\VendorPayoutRequest;
use App\Models\VendorTransaction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class VendorPayoutRequestResource extends Resource
{
    protected static ?string $model = VendorPayoutRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 13;

    protected static bool $isScopedToTenant = false;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.business_name')->searchable(),
                Tables\Columns\TextColumn::make('amount_ghs')->money('GHS'),
                Tables\Columns\TextColumn::make('payout_method'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('requested_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(VendorPayoutStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('info')
                    ->visible(fn (VendorPayoutRequest $record) => $record->status === VendorPayoutStatus::Pending)
                    ->requiresConfirmation()
                    ->action(fn (VendorPayoutRequest $record) => static::updateStatus($record, VendorPayoutStatus::Approved)),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (VendorPayoutRequest $record) => $record->status === VendorPayoutStatus::Pending)
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')->label('Reason')->required(),
                    ])
                    ->action(function (VendorPayoutRequest $record, array $data) {
                        $record->update(['admin_notes' => $data['admin_notes']]);
                        static::updateStatus($record, VendorPayoutStatus::Rejected);
                    }),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-m-currency-dollar')
                    ->color('success')
                    ->visible(fn (VendorPayoutRequest $record) => $record->status === VendorPayoutStatus::Approved)
                    ->requiresConfirmation()
                    ->action(function (VendorPayoutRequest $record) {
                        DB::transaction(function () use ($record) {
                            $wallet = $record->vendor->wallet;
                            $wallet->balance_ghs = (float) $wallet->balance_ghs - (float) $record->amount_ghs;
                            $wallet->save();

                            VendorTransaction::create([
                                'vendor_id' => $record->vendor_id,
                                'type' => VendorTransactionType::Payout->value,
                                'amount_ghs' => -$record->amount_ghs,
                                'balance_after_ghs' => $wallet->balance_ghs,
                                'description' => "Payout paid via {$record->payout_method}.",
                            ]);

                            static::updateStatus($record, VendorPayoutStatus::Paid);
                        });
                    }),
            ])
            ->defaultSort('requested_at', 'desc');
    }

    private static function updateStatus(VendorPayoutRequest $record, VendorPayoutStatus $status): void
    {
        $record->update([
            'status' => $status->value,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        Notification::make()->title("Payout {$status->value}")->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorPayoutRequests::route('/'),
            'view' => Pages\ViewVendorPayoutRequest::route('/{record}'),
        ];
    }
}
