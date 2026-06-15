<?php

namespace App\Filament\Resources\EcommerceProductResource\Pages;

use App\Filament\Resources\EcommerceProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEcommerceProduct extends CreateRecord
{
    protected static string $resource = EcommerceProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
