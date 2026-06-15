<?php

namespace App\Filament\Resources\EcommerceOrderRatingResource\Pages;

use App\Filament\Resources\EcommerceOrderRatingResource;
use Filament\Resources\Pages\ListRecords;

class ListEcommerceOrderRatings extends ListRecords
{
    protected static string $resource = EcommerceOrderRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
