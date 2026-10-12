<?php

namespace App\Filament\Resources\AuctionResource\RelationManagers;

use App\Models\AuctionBid;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only bid log. Bids are binding offers, so staff can see them but
 * never create, edit or delete them here.
 */
class BidsRelationManager extends RelationManager
{
    protected static string $relationship = 'bids';

    protected static ?string $title = 'Bids';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount_ghs')
            ->columns([
                Tables\Columns\TextColumn::make('amount_ghs')
                    ->label('Amount')
                    ->money('GHS')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Bidder')
                    ->description(fn (AuctionBid $record) => $record->user?->email),
                Tables\Columns\IconColumn::make('leading')
                    ->label('Leading')
                    ->boolean()
                    ->getStateUsing(fn (AuctionBid $record) => $record->id === $this->getOwnerRecord()->winning_bid_id),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime('j M Y, H:i:s'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
