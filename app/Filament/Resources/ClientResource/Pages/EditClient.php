<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

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

    public function afterSave(): void
    {
        //add new client to users records for login
        $record = $this->record;

        $email_or_phone = $record->email ? $record->email : $record->phone;

        $data = [
            'name' => $record->name,
            'email' =>  $email_or_phone,
            'phone' => $record->phone,
        ];

        User::where('email',$email_or_phone)
        ->update($data);
    }
}
