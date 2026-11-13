<?php

namespace App\Filament\Resources\EcommerceReturnResource\Pages;

use App\Filament\Resources\EcommerceReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEcommerceReturns extends ManageRecords
{
    protected static string $resource = EcommerceReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
