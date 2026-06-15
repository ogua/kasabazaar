<?php

namespace App\Filament\Resources\EcommerceProductResource\Pages;

use App\Filament\Resources\EcommerceProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEcommerceProducts extends ListRecords
{
    protected static string $resource = EcommerceProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
