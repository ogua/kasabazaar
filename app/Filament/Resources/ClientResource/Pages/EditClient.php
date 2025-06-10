<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Models\User;
use Filament\Actions;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ClientResource;

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

        if ($email_or_phone) {
            $data = [
                'name' => $record->name,
                'email' =>  $email_or_phone,
                'phone' => $record->phone,
            ];

            $check = User::where('email', $email_or_phone)->first();

            if ($check) {
                User::where('email', $email_or_phone)
                    ->update($data);
            } else {

                $data = [
                    'name' => $record->name,
                    'email' => $email_or_phone,
                    'phone' => $record->phone,
                    'password' => Hash::make('password'),
                    'role' => "customer",
                    'branch_id' => Filament::getTenant()->id,
                    'client_id' => $record->id
                ];

                User::create($data);
            }
        }
    }
}