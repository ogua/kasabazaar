<?php

namespace App\Filament\Resources\EcommerceInventoryLogResource\Pages;

use App\Filament\Resources\EcommerceInventoryLogResource;
use Filament\Resources\Pages\ListRecords;

class ListEcommerceInventoryLogs extends ListRecords
{
    protected static string $resource = EcommerceInventoryLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
