<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PaymentResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PaymentResource\RelationManagers;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $title = 'Transactions';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('created_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                ->description('')
                ->schema([
                Forms\Components\TextInput::make('branch_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('user_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('shipment_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('account_id')
                    ->maxLength(36)
                    ->default(null),
                Forms\Components\TextInput::make('payment_type'),
                Forms\Components\TextInput::make('payment_ref')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('paying_type')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('change')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('cheque_no')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('customer_stripe_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('charge_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('paypal_transaction_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('paying_method')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('payment_note')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('bankname')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DateTimePicker::make('paid_on'),
                Forms\Components\TextInput::make('accountnumber')
                    ->maxLength(255)
                    ->default(null),
                ])
                ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shipment.client.name')
                ->label('Client')
                ->searchable(),

                Tables\Columns\TextColumn::make('payment_ref')
                    ->searchable(),

                Tables\Columns\TextColumn::make('paying_method')
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('bankname')
                    ->label('Bank name')
                    ->description(fn($record) => $record->cheque_no)
                    ->searchable(),
                // Tables\Columns\TextColumn::make('cheque_no')
                // ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->badge()
                    ->prefix('$')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_on')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('enteredby.name')
                ->label('Recorded By')
                ->badge()
                ->color('info')
                ->placeholder('Online Payment')
                ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
