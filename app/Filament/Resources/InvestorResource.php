<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestorResource\Pages;
use App\Filament\Resources\InvestorResource\RelationManagers\InvestmentsRelationManager;
use App\Models\Investor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class InvestorResource extends Resource
{
    protected static ?string $model = Investor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Investors';

    protected static ?string $navigationLabel = 'Investors';

    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Investor Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(30),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\Select::make('id_type')
                            ->options([
                                'Drivers License' => 'Drivers License',
                                'Ghana Card' => 'Ghana Card',
                                'Voter ID Card' => 'Voter ID Card',
                                'Passport' => 'Passport',
                            ]),

                        Forms\Components\TextInput::make('id_number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('country')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('state_region')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(100),

                        Forms\Components\Textarea::make('address')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Payout Bank Details')
                    ->description('Used for automated Paystack transfer payouts. Optional for manual-only payouts.')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bank_code')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('account_number')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('account_name')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('investments_count')
                    ->label('Investments')
                    ->counts('investments'),

                Tables\Columns\TextColumn::make('investments_sum_current_balance')
                    ->label('Total Value')
                    ->sum('investments', 'current_balance')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('downloadStatement')
                    ->label('Download Statement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Investor $record) => URL::temporarySignedRoute('investment-account-statement', now()->addDay(), $record))
                    ->openUrlInNewTab(),
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
            InvestmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestors::route('/'),
            'create' => Pages\CreateInvestor::route('/create'),
            'view' => Pages\ViewInvestor::route('/{record}'),
            'edit' => Pages\EditInvestor::route('/{record}/edit'),
        ];
    }
}
