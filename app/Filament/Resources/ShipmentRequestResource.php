<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ShipmentRequest;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use App\Enums\ShipmentRequestStatus;
use Filament\Notifications\Notification;
use App\Services\ShipmentRequestApprovalService;
use App\Filament\Resources\ShipmentRequestResource\Pages;

class ShipmentRequestResource extends Resource
{
    protected static ?string $model = ShipmentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'Shipment Requests';

    //protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'submitted')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Details')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options(ShipmentRequestStatus::class)
                            ->disabled(),

                        Forms\Components\TextInput::make('pickup_location')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('preferred_pickup_at')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->disabled(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.phone')
                    ->label('Phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pickup_location')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->pickup_location),

                Tables\Columns\TextColumn::make('preferred_pickup_at')
                    ->label('Preferred Pickup')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ShipmentRequestStatus::class),
            ])
            ->actions([
                Action::make('markUnderReview')
                    ->label('Mark Under Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => $record->status->value === 'submitted')
                    ->action(function ($record) {
                        $record->update(['status' => 'under_review']);
                        Notification::make()->title('Marked as Under Review')->success()->send();
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status->value, ['submitted', 'under_review']))
                    ->form([
                        Forms\Components\TextInput::make('shipping_cost')
                            ->numeric()
                            ->prefix('USD')
                            ->nullable(),
                        Forms\Components\TextInput::make('vat_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Staff Notes')
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $service  = new ShipmentRequestApprovalService();
                            $shipment = $service->approve($record->load('client'), auth()->user(), $data);
                            Notification::make()
                                ->title('Request Approved')
                                ->body("Shipment {$shipment->shipping_reference} created.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Approval Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status->value !== 'approved' && $record->status->value !== 'rejected')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->required()
                            ->label('Reason for Rejection'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status'           => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_by'      => auth()->id(),
                            'reviewed_at'      => now(),
                        ]);
                        Notification::make()->title('Request Rejected')->warning()->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipmentRequests::route('/'),
            'view'  => Pages\ViewShipmentRequest::route('/{record}'),
        ];
    }
}
