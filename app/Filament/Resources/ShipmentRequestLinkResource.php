<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentRequestLinkResource\Pages;
use App\Models\Client;
use App\Models\ShipmentRequestLink;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Js;

/**
 * Unique public links that let a client submit a pickup (shipment) request
 * without logging in. Authorised through the Shipment Request permissions —
 * see ShipmentRequestLinkPolicy.
 */
class ShipmentRequestLinkResource extends Resource
{
    protected static ?string $model = ShipmentRequestLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Pickup Request Links';

    protected static ?string $modelLabel = 'pickup request link';

    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->placeholder('Open link (any client)')
                    ->searchable(),

                Tables\Columns\TextColumn::make('token')
                    ->label('Link')
                    ->formatStateUsing(fn (ShipmentRequestLink $record): string => $record->url())
                    ->icon('heroicon-m-clipboard-document')
                    ->iconPosition('after')
                    ->tooltip('Click to copy')
                    ->extraAttributes(fn (ShipmentRequestLink $record): array => [
                        'x-on:click.stop' => static::copyJs($record),
                        'class' => 'cursor-pointer',
                    ])
                    ->limit(40),

                Tables\Columns\TextColumn::make('state')
                    ->label('Status')
                    ->state(fn (ShipmentRequestLink $record): string => $record->state())
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'used' => 'info',
                        'expired' => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->formatStateUsing(fn (ShipmentRequestLink $record): string => $record->submissions_count.' / '.($record->max_submissions ?? '∞')),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('M d, Y H:i')
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('usable')
                    ->label('Active only')
                    ->query(fn ($query) => $query->usable())
                    ->default(),
            ])
            ->actions([
                Tables\Actions\Action::make('copy')
                    ->label('Copy')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->alpineClickHandler(fn (ShipmentRequestLink $record): string => static::copyJs($record)),

                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (ShipmentRequestLink $record): string => static::whatsappUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (ShipmentRequestLink $record): bool => $record->isUsable()),

                Tables\Actions\Action::make('revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('The client will no longer be able to submit a request through this link.')
                    ->visible(fn (ShipmentRequestLink $record): bool => ! $record->isRevoked()
                        && static::can('update', $record))
                    ->action(function (ShipmentRequestLink $record): void {
                        $record->update(['revoked_at' => now()]);
                        Notification::make()->title('Link revoked')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * The "Create Pickup Link" action, shared by this resource and the Shipment Requests list.
     */
    public static function createLinkAction(): Action
    {
        return Action::make('createPickupLink')
            ->label('Create Pickup Link')
            ->icon('heroicon-o-link')
            ->color('primary')
            ->visible(fn (): bool => static::can('create'))
            ->modalHeading('Create a pickup request link')
            ->modalDescription('Generates a unique link you can send to a client so they can request a pickup without logging in.')
            ->modalSubmitActionLabel('Create Link')
            ->form([
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->helperText('Optional. Leave empty for an open link — the client will enter their own name and phone.')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Client::query()
                        ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Client $client): array => [$client->id => static::clientLabel($client)])
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => ($client = Client::find($value)) ? static::clientLabel($client) : null)
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Full name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(30)
                            ->unique(Client::class, 'phone'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->maxLength(500),
                    ])
                    ->createOptionModalHeading('Add a new client')
                    ->createOptionUsing(fn (array $data): string => Client::create([
                        'branch_id' => Filament::getTenant()?->getKey(),
                        'name' => $data['name'],
                        'phone' => preg_replace('/[\s\-()]/', '', $data['phone']),
                        'email' => $data['email'] ?? null,
                        'address' => $data['address'] ?? null,
                    ])->getKey()),

                Forms\Components\TextInput::make('label')
                    ->helperText('Optional internal note, e.g. "Facebook enquiry" or "Accra walk-in".')
                    ->maxLength(255),

                Forms\Components\Select::make('expires_in_days')
                    ->label('Expires')
                    ->options([
                        1 => 'In 24 hours',
                        3 => 'In 3 days',
                        7 => 'In 7 days',
                        14 => 'In 14 days',
                        30 => 'In 30 days',
                        0 => 'Never',
                    ])
                    ->default(7)
                    ->required(),

                Forms\Components\TextInput::make('max_submissions')
                    ->label('Maximum submissions')
                    ->helperText('1 = single use. Leave empty for unlimited (e.g. a link posted for many clients).')
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
            ])
            ->action(function (array $data): void {
                $client = filled($data['client_id'] ?? null) ? Client::find($data['client_id']) : null;
                $days = (int) $data['expires_in_days'];

                $link = ShipmentRequestLink::create([
                    'token' => ShipmentRequestLink::generateToken(),
                    'label' => $data['label'] ?? null,
                    'client_id' => $client?->id,
                    'branch_id' => Filament::getTenant()?->getKey() ?? $client?->branch_id,
                    'max_submissions' => filled($data['max_submissions'] ?? null) ? (int) $data['max_submissions'] : null,
                    'expires_at' => $days > 0 ? now()->addDays($days) : null,
                    'created_by' => auth()->id(),
                ]);

                $link->setRelation('client', $client);

                Notification::make()
                    ->title('Pickup link created')
                    ->body($link->url())
                    ->success()
                    ->persistent()
                    ->actions([
                        NotificationAction::make('copy')
                            ->label('Copy link')
                            ->alpineClickHandler(static::copyJs($link)),
                        NotificationAction::make('open')
                            ->label('Open')
                            ->url($link->url(), shouldOpenInNewTab: true),
                        NotificationAction::make('whatsapp')
                            ->label('Send via WhatsApp')
                            ->url(static::whatsappUrl($link), shouldOpenInNewTab: true),
                        NotificationAction::make('all')
                            ->label('All links')
                            ->url(static::getUrl('index')),
                    ])
                    ->send();
            });
    }

    /**
     * Alpine expression copying the link (see resources/views/admin/copy-to-clipboard.blade.php).
     */
    public static function copyJs(ShipmentRequestLink $link): string
    {
        return 'window.kasaCopy('.Js::from($link->url()).')';
    }

    public static function whatsappUrl(ShipmentRequestLink $link): string
    {
        $greeting = $link->client ? "Hello {$link->client->name}," : 'Hello,';
        $message = $greeting.' please use this link to request a pickup from '
            .config('group.company.name').': '.$link->url();

        $phone = preg_replace('/\D/', '', (string) $link->client?->phone);

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            $phone = '233'.substr($phone, 1);
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    private static function clientLabel(Client $client): string
    {
        return trim($client->name.($client->phone ? " ({$client->phone})" : ''));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipmentRequestLinks::route('/'),
        ];
    }
}
