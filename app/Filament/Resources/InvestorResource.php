<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Investor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\URL;
use App\Filament\Resources\InvestorResource\Pages;
use App\Filament\Resources\InvestorResource\RelationManagers\InvestmentsRelationManager;

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

                Forms\Components\Section::make('Returns')
                    ->description("This investor's standing annual interest rate, applied to all of their investments every year unless a specific tranche has a one-off rate override for a given year.")
                    ->schema([
                        Forms\Components\TextInput::make('default_annual_rate')
                            ->label('Default Annual Rate')
                            ->numeric()
                            ->suffix('%')
                            ->helperText('Leave blank to fall back to the company-wide default rate for each year.'),
                    ]),

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

                Tables\Columns\TextColumn::make('default_annual_rate')
                    ->label('Rate')
                    ->suffix('%')
                    ->placeholder('Company default')
                    ->toggleable(),

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

                Tables\Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-identification')
                    ->color('warning')
                    ->visible(fn () => \App\Service\ImpersonationService::canImpersonate())
                    ->disabled(fn (Investor $record) => $record->users()->count() === 0)
                    ->tooltip(fn (Investor $record) => $record->users()->count() === 0
                        ? 'No portal login is linked to this investor yet — create one via Users.'
                        : null)
                    ->form(fn (Investor $record) => $record->users()->count() > 1 ? [
                        Forms\Components\Select::make('user_id')
                            ->label('Login As')
                            ->options($record->users()->pluck('name', 'id'))
                            ->required(),
                    ] : [])
                    ->requiresConfirmation()
                    ->modalHeading('Impersonate Investor')
                    ->modalDescription(fn (Investor $record) => "You will be logged in as {$record->name}'s portal user until you stop impersonating.")
                    ->action(function (Investor $record, array $data) {
                        $target = isset($data['user_id'])
                            ? $record->users()->find($data['user_id'])
                            : $record->users()->first();

                        if (! $target) {
                            \Filament\Notifications\Notification::make()
                                ->title('No portal login found for this investor')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (! \App\Service\ImpersonationService::canImpersonate()) {
                            abort(403);
                        }

                        if ($target->status !== \App\Enums\UserStatus::Active) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot impersonate an inactive user')
                                ->danger()
                                ->send();

                            return;
                        }

                        return redirect()->to(\App\Service\ImpersonationService::startUrl($target));
                    }),

                Tables\Actions\Action::make('downloadStatement')
                    ->label('Download Statement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Investor $record) => URL::temporarySignedRoute('investment-account-statement', now()->addDay(), $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('downloadAgreement')
                    ->label('Download Agreement')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->visible(fn (Investor $record) => $record->investments()->exists())
                    ->url(fn (Investor $record) => URL::temporarySignedRoute('investment-agreement-combined', now()->addDay(), [
                        'investor' => $record->id,
                        'as_of' => $record->defaultAsOfDate()->toDateString(),
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('downloadAgreementAsOf')
                    ->label('Download As Of...')
                    ->icon('heroicon-o-calendar')
                    ->color('gray')
                    ->visible(fn (Investor $record) => $record->investments()->exists())
                    ->form([
                        Forms\Components\DatePicker::make('as_of')
                            ->label('As Of Date')
                            ->default(fn (Investor $record) => $record->defaultAsOfDate())
                            ->required(),
                    ])
                    ->action(function (Investor $record, array $data) {
                        return redirect(URL::temporarySignedRoute('investment-agreement-combined', now()->addDay(), [
                            'investor' => $record->id,
                            'as_of' => $data['as_of'],
                        ]));
                    }),

                Tables\Actions\Action::make('sendAgreement')
                    ->label('Send Agreement')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn (Investor $record) => $record->investments()->exists())
                    ->requiresConfirmation()
                    ->modalHeading('Send Agreement to Investor')
                    ->modalDescription(fn (Investor $record) => $record->email
                        ? "This will email the investment agreement (covering all of {$record->name}'s tranches) to {$record->email}."
                        : "{$record->name} has no email address on file — add one before sending.")
                    ->action(function (Investor $record) {
                        $sent = \App\Service\InvestmentAgreementService::sendCombinedEmail($record, $record->defaultAsOfDate());

                        if ($sent) {
                            \Filament\Notifications\Notification::make()
                                ->title('Agreement sent to investor.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Could not send agreement')
                                ->body('The investor has no email address on file.')
                                ->danger()
                                ->send();
                        }
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
