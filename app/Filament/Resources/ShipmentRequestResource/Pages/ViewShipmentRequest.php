<?php

namespace App\Filament\Resources\ShipmentRequestResource\Pages;

use App\Filament\Resources\ShipmentRequestResource;
use App\Filament\Resources\ShipmentResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewShipmentRequest extends ViewRecord
{
    protected static string $resource = ShipmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('convert')
                ->label('Convert to Shipment')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->isEditable() && ! $this->getRecord()->shipment_id && ShipmentResource::canCreate())
                ->url(fn (): string => ShipmentResource::getUrl('from-request', ['shipmentRequest' => $this->getRecord()->id])),

            Actions\EditAction::make()
                ->visible(fn (): bool => $this->getRecord()->isEditable() && ShipmentRequestResource::canEdit($this->getRecord())),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Components\Section::make('Client & Status')
                ->schema([
                    Components\TextEntry::make('client.name')->label('Client'),
                    Components\TextEntry::make('client.phone')->label('Phone'),
                    Components\TextEntry::make('client.email')->label('Email'),
                    Components\TextEntry::make('status')->badge(),
                    Components\TextEntry::make('pickup_location'),
                    Components\TextEntry::make('preferred_pickup_at')->dateTime('M d, Y H:i'),
                    Components\TextEntry::make('source')
                        ->formatStateUsing(fn (?string $state, $record): string => $state === 'public_link'
                            ? 'Pickup link'.($record->link?->label ? " ({$record->link->label})" : '')
                            : 'App'),
                    Components\TextEntry::make('notes')->columnSpanFull(),
                    Components\TextEntry::make('rejection_reason')->columnSpanFull()
                        ->visible(fn ($record) => filled($record->rejection_reason)),
                ])->columns(3),

            Components\Section::make('Receivers & Items')
                ->schema([
                    Components\RepeatableEntry::make('receivers')
                        ->schema([
                            Components\TextEntry::make('name')->label('Name'),
                            Components\TextEntry::make('phone')->label('Phone'),
                            Components\TextEntry::make('country')->label('Country'),
                            Components\TextEntry::make('city')->label('City'),
                            Components\TextEntry::make('address')->label('Address')->columnSpanFull(),
                            Components\RepeatableEntry::make('items')
                                ->schema([
                                    Components\TextEntry::make('description')->label('Product'),
                                    Components\TextEntry::make('product_id')
                                        ->label('Catalogue')
                                        ->badge()
                                        ->formatStateUsing(fn (): string => 'Listed')
                                        ->color('success')
                                        ->placeholder('New product'),
                                    Components\TextEntry::make('quantity')->label('Qty'),
                                    Components\TextEntry::make('estimated_value')->label('Value')->money('USD')->placeholder('To be priced'),
                                    Components\TextEntry::make('details')->label('Description')->placeholder('—'),
                                ])
                                ->columns(5)
                                ->columnSpanFull(),
                        ])->columns(4),
                ]),
        ]);
    }
}
