<?php

namespace App\Filament\Resources\EcommerceCategoryResource\Pages;

use App\Filament\Resources\EcommerceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEcommerceCategory extends EditRecord
{
    protected static string $resource = EcommerceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
