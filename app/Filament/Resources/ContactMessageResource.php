<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ContactMessage;
use Filament\Resources\Resource;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\ContactMessageResource\Pages;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Customer Feedback';

    protected static ?string $navigationLabel = 'Contact Messages';

    protected static ?string $recordTitleAttribute = 'subject';

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
                Forms\Components\Section::make('Contact Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options(ContactMessage::STATUSES)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Reply')
                    ->schema([
                        Forms\Components\Textarea::make('reply')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('replied_by')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\DateTimePicker::make('replied_at')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('From')
                    ->description(fn ($record) => $record->email)
                    ->searchable(['name', 'email']),
                Tables\Columns\TextColumn::make('subject')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'read' => 'info',
                        'replied' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('replied_at')
                    ->label('Replied')
                    ->dateTime('M j, Y g:i A')
                    ->placeholder('Not replied')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ContactMessage::STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->mutateRecordDataUsing(function (array $data, ContactMessage $record): array {
                        if ($record->status === 'pending') {
                            $record->update(['status' => 'read']);
                        }
                        return $data;
                    }),
                Tables\Actions\Action::make('reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->modalHeading('Reply to Message')
                    ->modalDescription(fn (ContactMessage $record) => "Replying to {$record->name} ({$record->email})")
                    ->form([
                        Forms\Components\Textarea::make('reply')
                            ->label('Your Reply')
                            ->placeholder('Enter your reply message...')
                            ->required()
                            ->rows(5),
                    ])
                    ->action(function (array $data, ContactMessage $record): void {
                        $record->update([
                            'reply' => $data['reply'],
                            'replied_by' => Auth::user()->name,
                            'replied_at' => now(),
                            'status' => 'replied',
                        ]);
                    })
                    ->successNotificationTitle('Reply sent successfully'),
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
                Section::make('Contact Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label('Phone')
                            ->placeholder('Not provided')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'read' => 'info',
                                'replied' => 'success',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Section::make('Message')
                    ->schema([
                        TextEntry::make('subject')
                            ->label('Subject')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('message')
                            ->label('Message')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Received At')
                            ->dateTime('F j, Y g:i A'),
                    ]),

                Section::make('Reply')
                    ->schema([
                        TextEntry::make('reply')
                            ->label('Reply Message')
                            ->columnSpanFull()
                            ->placeholder('No reply yet'),
                        TextEntry::make('replied_by')
                            ->label('Replied By')
                            ->placeholder('Not yet replied'),
                        TextEntry::make('replied_at')
                            ->label('Replied At')
                            ->dateTime('F j, Y g:i A')
                            ->placeholder('Not yet replied'),
                    ])
                    ->collapsed(fn ($record) => empty($record->reply)),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'create' => Pages\CreateContactMessage::route('/create'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }
}
