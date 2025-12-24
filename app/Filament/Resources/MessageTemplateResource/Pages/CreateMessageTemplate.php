<?php

namespace App\Filament\Resources\MessageTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\MessageTemplateResource;

class CreateMessageTemplate extends CreateRecord
{
    protected static string $resource = MessageTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
