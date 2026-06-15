<?php

namespace App\Filament\Resources\EcommerceCategoryResource\Pages;

use App\Filament\Resources\EcommerceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEcommerceCategory extends CreateRecord
{
    protected static string $resource = EcommerceCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
