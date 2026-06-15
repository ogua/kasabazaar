<?php

namespace App\Filament\Resources\EcommerceProductResource\Pages;

use App\Filament\Resources\EcommerceProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEcommerceProduct extends EditRecord
{
    protected static string $resource = EcommerceProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
