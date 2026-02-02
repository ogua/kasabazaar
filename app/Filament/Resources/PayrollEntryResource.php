<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PayrollEntry;
use Filament\Resources\Resource;
use App\Enums\PayrollEntryStatus;
use App\Filament\Resources\PayrollEntryResource\Pages;

class PayrollEntryResource extends Resource
{
    protected static ?string $model = PayrollEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Payroll';
    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Employee & Period')
                    ->schema([
                        Forms\Components\Select::make('payroll_period_id')
                            ->relationship('payrollPeriod', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('staff_id')
                            ->relationship('staff', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(PayrollEntryStatus::class)
                            ->default(PayrollEntryStatus::Pending)
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Earnings')
                    ->schema([
                        Forms\Components\TextInput::make('base_salary')
                            ->numeric()
                            ->prefix('GHS')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) =>
                                static::calculateTotals($set, $get)),
                        Forms\Components\TextInput::make('allowances')
                            ->numeric()
                            ->prefix('GHS')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) =>
                                static::calculateTotals($set, $get)),
                        Forms\Components\TextInput::make('overtime')
                            ->numeric()
                            ->prefix('GHS')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) =>
                                static::calculateTotals($set, $get)),
                        Forms\Components\TextInput::make('bonus')
                            ->numeric()
                            ->prefix('GHS')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) =>
                                static::calculateTotals($set, $get)),
                        Forms\Components\TextInput::make('gross_pay')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(5),

                Forms\Components\Section::make('Deductions')
                    ->schema([
                        Forms\Components\TextInput::make('tax')
                            ->label('PAYE Tax')
                            ->numeric()
                            ->prefix('GHS')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) =>
                                static::calculateTotals($set, $get)),
                        Forms\Components\TextInput::make('ssnit')
                            ->label('SSNIT (Employee 5.5%)')
                            ->numeric()
                            ->prefix('GHS')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) =>
                                static::calculateTotals($set, $get)),
                        // Forms\Components\TextInput::make('ssnit_employer')
                        //     ->label('SSNIT (Employer 13%)')
                        //     ->numeric()
                        //     ->prefix('GHS')
                        //     ->default(0),
                        Forms\Components\TextInput::make('other_deductions')
                            ->numeric()
                            ->prefix('GHS')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) =>
                                static::calculateTotals($set, $get)),
                        Forms\Components\TextInput::make('total_deductions')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(5),

                Forms\Components\Section::make('Net Pay')
                    ->schema([
                        Forms\Components\TextInput::make('net_salary')
                            ->numeric()
                            ->prefix('GHS')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\DatePicker::make('paid_at'),
                        Forms\Components\Textarea::make('notes')
                        ->columnSpanFull()
                            ->rows(2),
                    ])
                    ->columns(3),
            ]);
    }

    protected static function calculateTotals(Forms\Set $set, Forms\Get $get): void
    {
        $basicSalary = floatval($get('base_salary') ?? 0);
        $allowances = floatval($get('allowances') ?? 0);
        $overtime = floatval($get('overtime') ?? 0);
        $bonus = floatval($get('bonus') ?? 0);

        $grossSalary = $basicSalary + $allowances + $overtime + $bonus;
        $set('gross_pay', $grossSalary);

        $taxPaye = floatval($get('tax') ?? 0);
        $ssnitEmployee = floatval($get('ssnit') ?? 0);
        $otherDeductions = floatval($get('other_deductions') ?? 0);

        $totalDeductions = $taxPaye + $ssnitEmployee + $otherDeductions;
        $set('total_deductions', $totalDeductions);

        $netSalary = $grossSalary - $totalDeductions;
        $set('net_salary', $netSalary);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payrollPeriod.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_salary')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gross_pay')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_deductions')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->money('GHS')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PayrollEntryStatus::class),
                Tables\Filters\SelectFilter::make('payroll_period_id')
                    ->relationship('payrollPeriod', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === PayrollEntryStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => PayrollEntryStatus::Paid,
                            'paid_at' => now(),
                        ]);
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
            'index' => Pages\ListPayrollEntries::route('/'),
            'create' => Pages\CreatePayrollEntry::route('/create'),
            'edit' => Pages\EditPayrollEntry::route('/{record}/edit'),
        ];
    }
}
