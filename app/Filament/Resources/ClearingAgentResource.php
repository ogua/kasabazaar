<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClearingAgentResource\Pages;
use App\Models\ClearingAgent;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClearingAgentResource extends Resource
{
    protected static ?string $model = ClearingAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Agent Details')
                    ->schema([
                        Forms\Components\Select::make('staff_id')
                            ->label('Link to Staff (optional)')
                            ->relationship('staff', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Only fill this in if the agent is an existing payroll staff member.')
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if (! $state) {
                                    return;
                                }

                                $staff = Staff::find($state);

                                if (! $staff) {
                                    return;
                                }

                                $set('name', $staff->name);
                                $set('phone', $staff->phone ?? null);
                                $set('email', $staff->email ?? null);
                            }),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Hired / Independent Agent Info')
                    ->description('Fill in when this agent is not a linked staff member (e.g. an external customs broker).')
                    ->schema([
                        Forms\Components\TextInput::make('id_type')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('id_number')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount(['containerClearances', 'shipmentDeliveries']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff_id')
                    ->label('Type')
                    ->badge()
                    ->state(fn (ClearingAgent $record) => $record->staff_id ? 'Staff' : 'Independent')
                    ->color(fn (ClearingAgent $record) => $record->staff_id ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('company')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('container_clearances_count')
                    ->label('Clearances')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipment_deliveries_count')
                    ->label('Deliveries')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('staff_id')
                    ->label('Roster type')
                    ->placeholder('All agents')
                    ->trueLabel('Staff-linked only')
                    ->falseLabel('Independent only')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('staff_id'),
                        false: fn ($query) => $query->whereNull('staff_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClearingAgents::route('/'),
            'create' => Pages\CreateClearingAgent::route('/create'),
            'edit' => Pages\EditClearingAgent::route('/{record}/edit'),
        ];
    }
}
