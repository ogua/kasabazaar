<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Staff;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PickupSchedule;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\PickupScheduleResource\Pages;

class PickupScheduleResource extends Resource
{
    protected static ?string $model = PickupSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Pickup Schedule';

    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pickup Details')
                    ->schema([
                        Forms\Components\Hidden::make('branch_id')
                            ->default(fn () => Filament::getTenant()?->id),

                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->createOptionForm(\App\Filament\Resources\ClientResource::clientschema())
                            ->afterStateUpdated(function (callable $set, ?string $state) {
                                if ($state) {
                                    $client = \App\Models\Client::find($state);
                                    $set('contact_phone', $client->phone);
                                    $set('pickup_location', $client->address);
                                }
                            }),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Scheduled Date & Time')
                            ->required()
                            ->native(false)
                            ->minDate(now()->subDay()),

                        Forms\Components\TextInput::make('pickup_location'),

                        Forms\Components\TextInput::make('contact_phone'),

                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned Staff')
                            ->options(Staff::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Select staff member'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(PickupSchedule::STATUSES)
                            ->default('scheduled')
                            ->required()
                            ->native(false),

                        // Forms\Components\Select::make('shipment_id')
                        //     ->label('Linked Shipment (optional)')
                        //     ->relationship('shipment', 'shipping_reference')
                        //     ->searchable()
                        //     ->preload()
                        //     ->placeholder('Link to an existing shipment'),

                        Forms\Components\Textarea::make('items_description')
                            ->label('Items Description')
                            ->placeholder('Brief description of items to be picked up')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('pickupItems')
                                ->relationship('pickupItems')
                                ->label('Items Scheduled for Pickup')
                                ->addActionLabel('+ Add Item')
                                ->defaultItems(1)
                                ->live()
                                ->collapsible()
                                ->schema([
                                Forms\Components\Select::make('product_id')
                                ->label('Item Name')
                                ->required()
                                ->relationship('product', 'name')
                                ->preload()
                                ->searchable()
                                ->createOptionForm(ProductResource::productself())
                                ->editOptionForm(ProductResource::productself()),

                          
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity')
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->placeholder('e.g., 3'),
                    ])
                    ->columns(2),

                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->color(function ($state) {
                        $date = \Carbon\Carbon::parse($state);
                        if ($date->isPast()) return 'danger';
                        if ($date->isToday()) return 'warning';
                        return 'success';
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('pickup_location')
                    ->label('Location')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'confirmed' => 'primary',
                        'in-progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => PickupSchedule::STATUSES[$state] ?? ucfirst($state)),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('shipment.shipping_reference')
                    ->label('Shipment')
                    ->placeholder('N/A')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('items_description')
                    ->label('Items')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PickupSchedule::STATUSES),
                SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->options(User::pluck('name', 'id')),
                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Only')
                    ->query(fn ($query) => $query->where('scheduled_at', '>=', now()))
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-m-arrow-path')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(PickupSchedule::STATUSES)
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])
                    ->fillForm(fn ($record) => [
                        'status' => $record->status,
                        'notes' => $record->notes,
                    ])
                    ->action(function ($record, array $data) {
                        $record->update($data);

                        \Filament\Notifications\Notification::make()
                            ->title('Status Updated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPickupSchedules::route('/'),
            'create' => Pages\CreatePickupSchedule::route('/create'),
            'edit' => Pages\EditPickupSchedule::route('/{record}/edit'),
        ];
    }
}
