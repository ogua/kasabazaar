<?php

namespace App\Filament\Resources\AuctionResource\Pages;

use App\Filament\Resources\AuctionResource;
use App\Models\Auction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAuction extends EditRecord
{
    protected static string $resource = AuctionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Once anyone has bid, the record is evidence of a binding offer — cancel it instead.
            Actions\DeleteAction::make()
                ->visible(fn (Auction $record) => $record->bids_count === 0),
        ];
    }

    /**
     * Keep original_ends_at in step while the schedule is still editable, so
     * anti-sniping extensions are always measured from the advertised end.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['ends_at']) && $this->record->bids_count === 0) {
            $data['original_ends_at'] = $data['ends_at'];
        }

        return $data;
    }
}
