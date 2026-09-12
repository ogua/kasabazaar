<?php

namespace App\Filament\Resources\ClearingAgentResource\Pages;

use App\Filament\Resources\ClearingAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClearingAgents extends ListRecords
{
    protected static string $resource = ClearingAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
