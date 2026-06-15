<?php

namespace App\Filament\Resources\EcommerceCategoryResource\Pages;

use App\Filament\Resources\EcommerceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEcommerceCategories extends ListRecords
{
    protected static string $resource = EcommerceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
