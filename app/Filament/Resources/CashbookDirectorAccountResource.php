<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashbookDirectorAccountResource\Pages;
use App\Models\CashbookDirectorAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashbookDirectorAccountResource extends Resource
{
    protected static ?string $model = CashbookDirectorAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Cashbook';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = "Director's Account";
    protected static ?string $modelLabel = "Director Account Entry";
    protected static ?string $pluralModelLabel = "Director's Account";
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Entry Details')
                    ->schema([
                        Forms\Components\DatePicker::make('date')->required(),
                        Forms\Components\TextInput::make('particulars')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('op_balance')
                            ->label('Opening Balance')
                            ->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('debit')
                            ->label('Debit (Funds Injected)')
                            ->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('credit')
                            ->label('Credit (Withdrawal)')
                            ->numeric()->prefix('₵')->default(0),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Closing Balance')
                    ->schema([
                        Forms\Components\Placeholder::make('cl_balance')
                            ->label('Closing Balance (auto-computed)')
                            ->content(fn ($record) => $record ? '₵' . number_format($record->cl_balance, 2) : 'Computed on save'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('particulars')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('op_balance')->label('Op. Bal')->numeric(2)->prefix('₵'),
                Tables\Columns\TextColumn::make('debit')->label('Debit')->numeric(2)->prefix('₵')->color('success'),
                Tables\Columns\TextColumn::make('credit')->label('Credit')->numeric(2)->prefix('₵')->color('danger'),
                Tables\Columns\TextColumn::make('cl_balance')->label('Cl. Bal')->numeric(2)->prefix('₵')->weight('bold'),
            ])
            ->defaultSort('date', 'asc')
            ->striped()
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCashbookDirectorAccounts::route('/'),
            'create' => Pages\CreateCashbookDirectorAccount::route('/create'),
            'edit'   => Pages\EditCashbookDirectorAccount::route('/{record}/edit'),
        ];
    }
}
