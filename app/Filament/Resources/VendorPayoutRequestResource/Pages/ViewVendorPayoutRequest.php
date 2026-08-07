<?php

namespace App\Filament\Resources\VendorPayoutRequestResource\Pages;

use App\Filament\Resources\VendorPayoutRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorPayoutRequest extends ViewRecord
{
    protected static string $resource = VendorPayoutRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
