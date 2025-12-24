<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ShipmentMessage;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ShipmentMessageResource\Pages;

class ShipmentMessageResource extends Resource
{
    protected static ?string $model = ShipmentMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Messaging';

    protected static ?string $navigationLabel = 'Sent Messages';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Message Details')
                ->schema([
                    Forms\Components\Select::make('target_type')
                        ->label('Send To')
                        ->options([
                            'client' => 'Specific Client',
                            'shipment' => 'Specific Shipment',
                            'container' => 'All in Container',
                            'all' => 'All Clients',
                        ])
                        ->required()
                        ->live()
                        ->native(false),

                    Forms\Components\Select::make('client_id')
                        ->label('Select Client')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get) => $get('target_type') === 'client'),

                    Forms\Components\Select::make('shipment_id')
                        ->label('Select Shipment')
                        ->relationship('shipment', 'shipping_reference')
                        ->searchable()
                        ->preload()
                        ->visible(fn ($get) => $get('target_type') === 'shipment'),

                    Forms\Components\Select::make('container_number')
                        ->label('Container Number')
                        ->options(function () {
                            return \App\Models\Shipment::whereNotNull('container_number')
                                ->distinct()
                                ->orderByDesc('container_number')
                                ->pluck('container_number', 'container_number')
                                ->mapWithKeys(fn ($num) => [$num => "Container #$num"]);
                        })
                        ->searchable()
                        ->visible(fn ($get) => $get('target_type') === 'container'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Message Content')
                ->schema([
                    Forms\Components\Select::make('message_template_id')
                        ->label('Use Template')
                        ->relationship('template', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $template = \App\Models\MessageTemplate::find($state);
                                if ($template) {
                                    $set('subject', $template->subject);
                                    $set('body', $template->body);
                                    $set('channel', $template->type);
                                }
                            }
                        }),

                    Forms\Components\TextInput::make('subject')
                        ->label('Subject')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\RichEditor::make('body')
                        ->label('Message Body')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('channel')
                        ->label('Send Via')
                        ->options([
                            'email' => 'Email Only',
                            'sms' => 'SMS Only',
                            'both' => 'Both Email & SMS',
                        ])
                        ->default('email')
                        ->required()
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'client' => 'info',
                        'shipment' => 'success',
                        'container' => 'warning',
                        'all' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('shipment.shipping_reference')
                    ->label('Shipment')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('container_number')
                    ->label('Container')
                    ->formatStateUsing(fn ($state) => $state ? "#$state" : '-'),

                Tables\Columns\BadgeColumn::make('channel')
                    ->label('Channel')
                    ->colors([
                        'primary' => 'email',
                        'success' => 'sms',
                        'warning' => 'both',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'sent',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Sent By'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('target_type')
                    ->options([
                        'client' => 'Client',
                        'shipment' => 'Shipment',
                        'container' => 'Container',
                        'all' => 'All',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'both' => 'Both',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'failed')
                    ->action(function ($record) {
                        \App\Service\ShipmentMessageService::processMessage($record);
                        \Filament\Notifications\Notification::make()
                            ->title('Message Resent')
                            ->success()
                            ->send();
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipmentMessages::route('/'),
            'create' => Pages\CreateShipmentMessage::route('/create'),
        ];
    }
}
