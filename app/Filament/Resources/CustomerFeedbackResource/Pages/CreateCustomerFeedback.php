<?php

namespace App\Filament\Resources\CustomerFeedbackResource\Pages;

use App\Filament\Resources\CustomerFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerFeedback extends CreateRecord
{
    protected static string $resource = CustomerFeedbackResource::class;
}
