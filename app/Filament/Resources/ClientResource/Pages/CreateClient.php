<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Actions;
use App\Models\Client;
use App\Services\CustomerAccountProvisioner;
use Filament\Facades\Filament;
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
        // Give the client a mobile-app login with a random password, and invite
        // them to set their own. Never assign a shared default: these accounts
        // gate payment history, saved addresses and in-app checkout.
        $result = app(CustomerAccountProvisioner::class)
            ->provision($this->record, Filament::getTenant()?->id);

        match ($result['status']) {
            CustomerAccountProvisioner::CREATED_INVITED => Notification::make()
                ->title('Client created and invited')
                ->body('A password set-up link has been emailed to the client.')
                ->success()
                ->send(),

            CustomerAccountProvisioner::CREATED_NO_EMAIL => Notification::make()
                ->title('Login created, but no invite sent')
                ->body('This client has no email address, so they cannot set a password. Add an email and save to send the invite.')
                ->warning()
                ->persistent()
                ->send(),

            CustomerAccountProvisioner::ALREADY_EXISTS => Notification::make()
                ->title('An account already uses this email or phone')
                ->body('The existing login was left untouched.')
                ->warning()
                ->send(),

            default => Notification::make()
                ->title('No login created')
                ->body('Add an email address or phone number to give this client app access.')
                ->warning()
                ->send(),
        };
    }
}
