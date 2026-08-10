<?php

namespace App\Filament\Resources\EcommerceVendorApplicationResource\Pages;

use App\Filament\Resources\EcommerceVendorApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListEcommerceVendorApplications extends ListRecords
{
    protected static string $resource = EcommerceVendorApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
