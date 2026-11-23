<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManualReceiptResource\Pages;
use App\Models\Client;
use App\Models\ManualReceipt;
use App\Services\ManualReceiptService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * "Receipt Generator": staff type in a payment the company already received outside the system
 * (mostly 2019 onwards, before this app went live) and get a receipt to print or send.
 * Saving one never records money — no Payment, Income or cashbook entry is created.
 */
class ManualReceiptResource extends Resource
{
    protected static ?string $model = ManualReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Receipt Generator';

    protected static ?string $modelLabel = 'Manual Receipt';

    protected static ?string $pluralModelLabel = 'Manual Receipts';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'receipt_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('about')
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-600 dark:bg-amber-950 dark:text-amber-100">'
                        .'<strong>Receipt only.</strong> Use this for payments the company has <em>already received</em> '
                        .'(for example before this system was in use) when a client asks for a receipt. '
                        .'It does <strong>not</strong> record a payment, change any balance or touch the cashbook.'
                        .'</div>'
                    ))
                    ->columnSpanFull(),

                Forms\Components\Section::make('1. Who paid?')
                    ->description('Search by name, phone or email. If the client is not in the system yet, click the + button to add them.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable(['name', 'phone', 'email'])
                            ->getOptionLabelFromRecordUsing(fn (Client $record): string => collect([$record->name, $record->phone, $record->email])->filter()->implode(' · '))
                            ->required()
                            ->createOptionForm(ClientResource::clientschema())
                            ->createOptionAction(fn (Forms\Components\Actions\Action $action) => $action
                                ->modalHeading('Add a new client')
                                ->modalSubmitActionLabel('Save client')
                                ->modalWidth('2xl'))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('2. Payment details')
                    ->description('Enter the payment exactly as it was received.')
                    ->icon('heroicon-o-banknotes')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\ToggleButtons::make('currency')
                            ->label('Currency')
                            ->options(ManualReceipt::CURRENCIES)
                            ->default('USD')
                            ->inline()
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount paid')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->required()
                            ->prefix(fn (Get $get): string => $get('currency') === 'GHS' ? 'GH₵' : '$'),

                        Forms\Components\DatePicker::make('paid_on')
                            ->label('Date paid')
                            ->native(false)
                            ->displayFormat('j M Y')
                            ->maxDate(now())
                            ->minDate('2010-01-01')
                            ->required(),

                        Forms\Components\TextInput::make('payment_method')
                            ->label('Payment method')
                            ->placeholder('e.g. Cash, Mobile Money, Bank Transfer')
                            ->datalist(ManualReceipt::PAYMENT_METHODS)
                            ->helperText('Pick a suggestion or type your own.')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('description')
                            ->label('Payment for')
                            ->default('Shipping charges')
                            ->datalist([
                                'Shipping charges',
                                'Vehicle shipping',
                                'Container shipping',
                                'Door-to-door delivery',
                                'Customs clearing',
                                'Deposit / part payment',
                            ])
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('related_reference')
                            ->label('Shipment / invoice reference')
                            ->placeholder('Optional — e.g. an old invoice number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction / cheque reference')
                            ->placeholder('Optional — MoMo ID, bank slip no., cheque no.')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('received_by')
                            ->label('Received by')
                            ->placeholder('Optional — staff who took the payment')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('3. Notes (optional)')
                    ->icon('heroicon-o-pencil-square')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes printed on the receipt')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No receipts generated yet')
            ->emptyStateDescription('Click "Generate receipt" to create a receipt for a payment received outside the system.')
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Receipt No')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->description(fn (ManualReceipt $record): ?string => $record->client?->phone)
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (ManualReceipt $record): string => $record->formattedAmount())
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('paid_on')
                    ->label('Date Paid')
                    ->date('j M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('issuer.name')
                    ->label('Issued By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_sent_at')
                    ->label('Last Sent')
                    ->since()
                    ->placeholder('Not sent')
                    ->tooltip(fn (ManualReceipt $record): ?string => $record->last_sent_to)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Issued On')
                    ->dateTime('j M Y, g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->options(ManualReceipt::CURRENCIES),

                Tables\Filters\Filter::make('paid_on')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Paid from'),
                        Forms\Components\DatePicker::make('until')->label('Paid until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('paid_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('paid_on', '<=', $date))),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (ManualReceipt $record): string => app(ManualReceiptService::class)->viewUrl($record), shouldOpenInNewTab: true),

                static::configureSendAction(Tables\Actions\Action::make('send')),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('download')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (ManualReceipt $record): string => app(ManualReceiptService::class)->downloadUrl($record)),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Receipt')
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        Infolists\Components\TextEntry::make('receipt_number')->label('Receipt No')->weight('bold')->copyable(),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Amount Paid')
                            ->formatStateUsing(fn (ManualReceipt $record): string => $record->formattedAmount())
                            ->weight('bold')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('paid_on')->label('Date Paid')->date('j M Y'),
                        Infolists\Components\TextEntry::make('payment_method')->label('Method')->badge()->color('gray'),
                        Infolists\Components\TextEntry::make('description')->label('Payment For'),
                        Infolists\Components\TextEntry::make('related_reference')->label('Shipment / Invoice')->placeholder('—'),
                        Infolists\Components\TextEntry::make('transaction_reference')->label('Transaction Ref')->placeholder('—'),
                        Infolists\Components\TextEntry::make('received_by')->label('Received By')->placeholder('—'),
                        Infolists\Components\TextEntry::make('amount_words')
                            ->label('Amount in Words')
                            ->state(fn (ManualReceipt $record): string => $record->amountInWords()),
                        Infolists\Components\TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Client')
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        Infolists\Components\TextEntry::make('client.name')->label('Name'),
                        Infolists\Components\TextEntry::make('client.phone')->label('Phone')->placeholder('—'),
                        Infolists\Components\TextEntry::make('client.email')->label('Email')->placeholder('—'),
                    ]),

                Infolists\Components\Section::make('History')
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        Infolists\Components\TextEntry::make('issuer.name')->label('Issued By')->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')->label('Issued On')->dateTime('j M Y, g:i A'),
                        Infolists\Components\TextEntry::make('last_sent_at')
                            ->label('Last Sent')
                            ->dateTime('j M Y, g:i A')
                            ->placeholder('Not sent yet')
                            ->helperText(fn (ManualReceipt $record): ?string => $record->last_sent_to
                                ? 'To '.$record->last_sent_to.' ('.$record->times_sent.'× in total)'
                                : null),
                    ]),
            ]);
    }

    /**
     * The "Send to client" action, shared by the table row and the view page header.
     *
     * @template T of \Filament\Actions\Action|\Filament\Tables\Actions\Action
     *
     * @param  T  $action
     * @return T
     */
    public static function configureSendAction($action)
    {
        return $action
            ->label('Send to client')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->modalHeading(fn (ManualReceipt $record): string => 'Send receipt '.$record->receipt_number)
            ->modalDescription('The email carries the receipt as a PDF; the SMS carries a link to it. Fill in either or both.')
            ->modalSubmitActionLabel('Send receipt')
            ->modalWidth('lg')
            ->fillForm(fn (ManualReceipt $record): array => [
                'email' => $record->client?->email,
                'phone' => $record->client?->phone,
            ])
            ->form([
                Forms\Components\TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->requiredWithout('phone')
                    ->validationMessages(['required_without' => 'Enter an email address or a phone number.']),
                PhoneInput::make('phone')
                    ->label('Phone number (SMS)'),
            ])
            ->action(function (ManualReceipt $record, array $data): void {
                try {
                    $sent = app(ManualReceiptService::class)->send($record, $data['email'] ?? null, $data['phone'] ?? null);
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()->title('The receipt could not be sent')->body($e->getMessage())->danger()->send();

                    return;
                }

                if ($sent === []) {
                    Notification::make()->title('Nothing was sent — add an email or phone number.')->warning()->send();

                    return;
                }

                Notification::make()
                    ->title('Receipt sent by '.implode(' and ', array_map(fn (string $channel): string => $channel === 'sms' ? 'SMS' : 'email', $sent)))
                    ->success()
                    ->send();
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['client', 'issuer'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['receipt_number', 'client.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManualReceipts::route('/'),
            'create' => Pages\CreateManualReceipt::route('/create'),
            'view' => Pages\ViewManualReceipt::route('/{record}'),
            'edit' => Pages\EditManualReceipt::route('/{record}/edit'),
        ];
    }
}
