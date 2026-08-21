<?php

namespace App\Filament\Investor\Resources;

use App\Enums\InvestmentAgreementStatus;
use App\Enums\InvestmentCapitalType;
use App\Filament\Investor\Resources\InvestmentResource\Pages;
use App\Filament\Investor\Resources\InvestmentResource\RelationManagers\TransactionsRelationManager;
use App\Models\Investment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class InvestmentResource extends Resource
{
    protected static ?string $model = Investment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'My Investments';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('investor_id', auth()->user()->investor_id)
            ->orderBy('start_date', 'desc');
    }

    // Investors authorize by ownership (scoped query above), not by the Shield
    // permissions the shared Investment model's policy requires for admin staff.
    public static function canViewAny(): bool
    {
        return filled(auth()->user()?->investor_id);
    }

    public static function canView($record): bool
    {
        return $record->investor_id === auth()->user()->investor_id;
    }

    public static function canCreate(): bool
    {
        return filled(auth()->user()?->investor_id);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('investor_id', auth()->user()->investor_id)
            ->where('status', 'active')
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('New Investment')
                    ->description('Choose an amount and a payment method. You\'ll be taken to a secure checkout to complete the deposit — your investment activates automatically once payment is confirmed.')
                    ->schema([
                        Forms\Components\TextInput::make('principal_amount')
                            ->label('Amount to Invest')
                            ->numeric()
                            ->prefix('USD')
                            ->minValue(0.01)
                            ->required(),

                        Forms\Components\Select::make('deposit_gateway')
                            ->label('Payment Method')
                            ->options([
                                'paystack' => 'Paystack (Mobile Money / Ghana Card / Bank)',
                                'stripe' => 'Stripe (Card, International)',
                            ])
                            ->default('paystack')
                            ->required(),
                    ])
                    ->columns(1)
                    ->visible(fn (?Investment $record) => $record === null),

                Forms\Components\Hidden::make('capital_type')->default('investment'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),

                Tables\Columns\TextColumn::make('principal_amount')
                    ->label('Principal')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Current Value')
                    ->money('USD')
                    ->tooltip(fn (Investment $record) => $record->capital_type === InvestmentCapitalType::loan
                        ? 'Principal only — a loan\'s balance never compounds. See Interest Owed for accrued interest.'
                        : null),

                Tables\Columns\TextColumn::make('interest_owed')
                    ->label('Interest Owed')
                    ->state(fn (Investment $record) => $record->capital_type === InvestmentCapitalType::loan
                        ? $record->interestPayouts()
                            ->whereIn('status', ['due', 'processing'])
                            ->get()
                            ->sum(fn ($payout) => (float) $payout->amount - (float) $payout->amount_paid)
                        : null)
                    ->money('USD')
                    ->placeholder('—')
                    ->tooltip('Loan interest accrued but not yet paid to you.'),

                Tables\Columns\TextColumn::make('capital_type')
                    ->label('Capital Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('agreement_status')
                    ->label('Agreement')
                    ->badge(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('maturity_date')
                    ->label('Contract Due')
                    ->date('M d, Y')
                    ->color(fn (Investment $record) => $record->isContractDue() ? 'success' : 'gray')
                    ->tooltip(fn (Investment $record) => $record->isContractDue()
                        ? 'Your contract has matured — you may request a withdrawal.'
                        : 'Withdrawal requests open once your contract term ends.')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('downloadAgreement')
                    ->label(fn (Investment $record) => $record->status->value === 'pending_payment' ? 'Preview Agreement' : 'Download Agreement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Investment $record) => URL::temporarySignedRoute('investment-agreement', now()->addDay(), [
                        'investment' => $record->id,
                        'as_of' => $record->defaultAsOfDate()->toDateString(),
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('uploadSignedAgreement')
                    ->label(fn (Investment $record) => $record->agreement_status === InvestmentAgreementStatus::pending_review
                        ? 'Signed Agreement Under Review'
                        : 'Upload Signed Agreement')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->disabled(fn (Investment $record) => $record->agreement_status === InvestmentAgreementStatus::pending_review)
                    ->visible(fn (Investment $record) => $record->agreement_status !== InvestmentAgreementStatus::finalized
                        && $record->status->value !== 'pending_payment')
                    ->modalDescription('Your agreement already carries your name as your signature — there is nothing to print or sign by hand. Download it, read it in full, then upload the same document back here to record your agreement to its terms. Our team will review it and confirm once finalized.')
                    ->form([
                        Forms\Components\FileUpload::make('signed_agreement_path')
                            ->label('Agreement Document')
                            ->helperText('Upload the agreement you downloaded above.')
                            ->directory('investment-agreements/signed')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->required(),

                        // The name is affixed for the investor, so the upload is the act
                        // of assent rather than the signing. This confirmation, with the
                        // originating IP recorded below, is what evidences that assent.
                        Forms\Components\Checkbox::make('acknowledged')
                            ->label('I have read this agreement in full and agree to be bound by its terms.')
                            ->accepted()
                            ->required()
                            ->validationMessages([
                                'accepted' => 'You must confirm that you have read and agree to the terms of this agreement.',
                            ]),
                    ])
                    ->action(function (Investment $record, array $data) {
                        $record->update([
                            'signed_agreement_path' => $data['signed_agreement_path'],
                            'agreement_status' => InvestmentAgreementStatus::pending_review,
                            'agreement_signed_at' => now(),
                            'agreement_acknowledged_ip' => request()->ip(),
                        ]);

                        Notification::make()
                            ->title('Signed agreement uploaded')
                            ->body('Our team will review it and confirm once finalized.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestments::route('/'),
            'create' => Pages\CreateInvestment::route('/create'),
            'view' => Pages\ViewInvestment::route('/{record}'),
        ];
    }
}
