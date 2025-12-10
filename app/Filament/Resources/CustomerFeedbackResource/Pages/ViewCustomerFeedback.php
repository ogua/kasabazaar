<?php

namespace App\Filament\Resources\CustomerFeedbackResource\Pages;

use App\Filament\Resources\CustomerFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerFeedback extends ViewRecord
{
    protected static string $resource = CustomerFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
