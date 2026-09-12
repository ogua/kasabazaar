<?php

namespace App\Filament\Resources\ClearingAgentResource\Pages;

use App\Filament\Resources\ClearingAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClearingAgent extends EditRecord
{
    protected static string $resource = ClearingAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
