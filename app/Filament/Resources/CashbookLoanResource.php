<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashbookLoanResource\Pages;
use App\Models\CashbookLoan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashbookLoanResource extends Resource
{
    protected static ?string $model = CashbookLoan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Cashbook';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Loan Schedule';
    protected static ?string $modelLabel = 'Loan Entry';
    protected static ?string $pluralModelLabel = 'Loan Schedule';
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Loan Entry')
                    ->schema([
                        Forms\Components\TextInput::make('lender_name')
                            ->label('Lender')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date')->required(),
                        Forms\Components\TextInput::make('particulars')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('op_balance')
                            ->label('Opening Balance')
                            ->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('debit')
                            ->label('Debit (Payment Made)')
                            ->numeric()->prefix('₵')->default(0),
                        Forms\Components\TextInput::make('credit')
                            ->label('Credit (Loan Received)')
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
                Tables\Columns\TextColumn::make('lender_name')->label('Lender')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('particulars')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('op_balance')->label('Op. Bal')->numeric(2)->prefix('₵'),
                Tables\Columns\TextColumn::make('debit')->label('Payment')->numeric(2)->prefix('₵')->color('danger'),
                Tables\Columns\TextColumn::make('credit')->label('Received')->numeric(2)->prefix('₵')->color('success'),
                Tables\Columns\TextColumn::make('cl_balance')->label('Balance')->numeric(2)->prefix('₵')->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lender_name')
                    ->label('Lender')
                    ->options(fn () => CashbookLoan::query()
                        ->distinct()
                        ->pluck('lender_name', 'lender_name')
                        ->toArray()),
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
            'index'  => Pages\ListCashbookLoans::route('/'),
            'create' => Pages\CreateCashbookLoan::route('/create'),
            'edit'   => Pages\EditCashbookLoan::route('/{record}/edit'),
        ];
    }
}
