<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\PayrollStatus;
use App\Models\PayrollPeriod;
use Filament\Resources\Resource;
use App\Filament\Resources\PayrollPeriodResource\Pages;

class PayrollPeriodResource extends Resource
{
    protected static ?string $model = PayrollPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Payroll';
    protected static ?int $navigationSort = 1;
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Period Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('e.g., "January 2026 Payroll"'),
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->after('start_date'),
                        Forms\Components\DatePicker::make('pay_date')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Branch')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(PayrollStatus::class)
                            ->default(PayrollStatus::Draft)
                            ->required(),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Totals')
                    ->schema([
                        Forms\Components\TextInput::make('total_gross')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('total_deductions')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('total_net')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record !== null),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pay_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_gross')
                    ->money('GHS')
                    ->state(fn($record) => $record->entries()->sum('gross_pay') ?? 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_net')
                    ->money('GHS')
                    ->state(fn($record) => $record->entries()->sum('net_salary') ?? 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('entries_count')
                    ->counts('entries')
                    ->label('Staff'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PayrollStatus::class),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('process')
                    ->label('Process')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === PayrollStatus::Draft)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => PayrollStatus::Processing]);
                        // In a real app, dispatch a job to process payroll
                    }),
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
            'index' => Pages\ListPayrollPeriods::route('/'),
            'create' => Pages\CreatePayrollPeriod::route('/create'),
            'edit' => Pages\EditPayrollPeriod::route('/{record}/edit'),
        ];
    }
}
