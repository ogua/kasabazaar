<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Shipment;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CustomerFeedback;
use Filament\Resources\Resource;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use App\Filament\Resources\CustomerFeedbackResource\Pages;

class CustomerFeedbackResource extends Resource
{
    protected static ?string $model = CustomerFeedback::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left';

    protected static ?string $navigationGroup = 'Customer Feedback';

    protected static ?string $navigationLabel = 'Feedback';

    protected static ?string $recordTitleAttribute = 'customer_name';

    protected static bool $isScopedToTenant = false;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feedback Details')
                    ->description('View and manage customer feedback')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                        Forms\Components\Select::make('shipment_id')
                            ->label('Related Shipment')
                            ->relationship('shipment', 'shipping_reference')
                            ->preload()
                            ->searchable()
                            ->placeholder('Select a shipment (optional)'),
                        Forms\Components\Select::make('feedback_on')
                            ->label('Service')
                            ->options(CustomerFeedback::FEEDBACK_SOURCES)
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Phone Number')
                            ->tel()
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice/Reference Number')
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->label('Feedback Category')
                            ->options(CustomerFeedback::CATEGORIES)
                            ->required()
                            ->searchable()
                            ->native(false),
                        Forms\Components\Select::make('rating')
                            ->label('Rating')
                            ->options([
                                1 => '1 - Very Poor',
                                2 => '2 - Poor',
                                3 => '3 - Average',
                                4 => '4 - Good',
                                5 => '5 - Excellent',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(CustomerFeedback::STATUSES)
                            ->required()
                            ->native(false)
                            ->default('pending'),
                        Forms\Components\Textarea::make('comment')
                            ->label('Customer Comment')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Attachments')
                            ->multiple()
                            ->image()
                            ->directory('feedback-attachments')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Response')
                    ->description('Staff response to customer feedback')
                    ->schema([
                        Forms\Components\Textarea::make('response')
                            ->label('Response Message')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('responded_by')
                            ->label('Responded By')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn ($record) => $record->customer_email)
                    ->searchable(['customer_name', 'customer_email']),
                Tables\Columns\TextColumn::make('feedback_on')
                    ->label('Service')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Rose Shipment' => 'primary',
                        'NeoRide Africa' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->limit(20),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->color(fn ($state) => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(CustomerFeedback::STATUSES),
                SelectFilter::make('feedback_on')
                    ->label('Service')
                    ->options(CustomerFeedback::FEEDBACK_SOURCES),
                SelectFilter::make('category')
                    ->options(CustomerFeedback::CATEGORIES),
                SelectFilter::make('rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('respond')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->modalHeading('Respond to Customer Feedback')
                    ->modalDescription(fn (CustomerFeedback $record) => "Responding to feedback from {$record->customer_name}")
                    ->form([
                        Select::make('status')
                            ->label('Update Status')
                            ->options(CustomerFeedback::STATUSES)
                            ->default(fn (CustomerFeedback $record) => $record->status)
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('response')
                            ->label('Response Message')
                            ->placeholder('Enter your response to the customer...')
                            ->required()
                            ->rows(5),
                    ])
                    ->action(function (array $data, CustomerFeedback $record): void {
                        $record->update([
                            'response' => $data['response'],
                            'responded_by' => Auth::user()->name,
                            'status' => $data['status'],
                        ]);
                    })
                    ->successNotificationTitle('Response sent successfully'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Customer Information')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Name')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('customer_email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('customer_phone')
                            ->label('Phone')
                            ->copyable(),
                        TextEntry::make('invoice_number')
                            ->label('Invoice #')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(2),

                Section::make('Feedback Details')
                    ->schema([
                        TextEntry::make('feedback_on')
                            ->label('Service')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Rose Shipment' => 'primary',
                                'NeoRide Africa' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('category')
                            ->label('Category')
                            ->badge(),
                        TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn ($state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                            ->color(fn ($state) => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'reviewed' => 'info',
                                'resolved' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('comment')
                            ->label('Customer Comment')
                            ->columnSpanFull()
                            ->placeholder('No comment provided'),
                        ImageEntry::make('attachments')
                            ->label('Attachments')
                            ->columnSpanFull()
                            ->stacked()
                            ->circular(false)
                            ->size(150),
                    ])
                    ->columns(2),

                Section::make('Response')
                    ->schema([
                        TextEntry::make('response')
                            ->label('Staff Response')
                            ->columnSpanFull()
                            ->placeholder('No response yet'),
                        TextEntry::make('responded_by')
                            ->label('Responded By')
                            ->placeholder('Not yet responded'),
                    ])
                    ->collapsed(fn ($record) => empty($record->response)),

                Section::make('Timestamps')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Submitted At')
                            ->dateTime('F j, Y g:i A'),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('F j, Y g:i A'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerFeedback::route('/'),
            'create' => Pages\CreateCustomerFeedback::route('/create'),
            'view' => Pages\ViewCustomerFeedback::route('/{record}'),
            'edit' => Pages\EditCustomerFeedback::route('/{record}/edit'),
        ];
    }
}
