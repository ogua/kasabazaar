<?php

namespace App\Filament\Resources\CustomerFeedbackResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\CustomerFeedbackResource;

class ListCustomerFeedback extends ListRecords
{
    protected static string $resource = CustomerFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            # Actions\CreateAction::make(),
        ];
    }
}
