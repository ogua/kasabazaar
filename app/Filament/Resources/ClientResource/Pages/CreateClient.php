<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Models\Client;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    public function beforeCreate(): void
    {
        $data = $this->data;

        //check if client exit
        $client = Client::where('email', $data['email'])
            ->where('phone', $data['phone'])
            ->first();

        if ($client) {

            Notification::make()
                ->title('Client already exist in the system')
                ->warning()
                ->persistent()
                ->send();

            //halt creation process
            $this->halt();
        }
    }


    public function afterCreate(): void
    {
        //add new client to users records for login
        $record = $this->record;

        $email_or_phone = $record->email ? $record->email : $record->phone;

        if ($email_or_phone) {
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
