<?php

namespace App\Filament\Resources\ShipmentRequestResource\Pages;

use App\Filament\Resources\ShipmentRequestResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewShipmentRequest extends ViewRecord
{
    protected static string $resource = ShipmentRequestResource::class;

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
                        ])->columns(4),
                ]),
        ]);
    }
}
