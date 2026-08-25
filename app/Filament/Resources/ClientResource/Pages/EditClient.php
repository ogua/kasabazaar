<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Models\User;
use App\Services\CustomerAccountProvisioner;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
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
        $record = $this->record;
        $login = $record->email ?: $record->phone;

        if (! $login) {
            return;
        }

        $existing = User::where('client_id', $record->id)->first()
            ?? User::where('email', $login)->first();

        if ($existing) {
            // Keep the login in step with the client record. The password is
            // deliberately untouched — editing a client must never reset it.
            $existing->update([
                'name' => $record->name,
                'email' => $login,
                'phone' => $record->phone,
                'client_id' => $record->id,
            ]);

            return;
        }

        // The client had no login (e.g. an email was only just added), so
        // provision one now and invite them to set a password.
        $result = app(CustomerAccountProvisioner::class)
            ->provision($record, Filament::getTenant()?->id);

        if ($result['status'] === CustomerAccountProvisioner::CREATED_INVITED) {
            Notification::make()
                ->title('Login created and invited')
                ->body('A password set-up link has been emailed to the client.')
                ->success()
                ->send();
        } elseif ($result['status'] === CustomerAccountProvisioner::CREATED_NO_EMAIL) {
            Notification::make()
                ->title('Login created, but no invite sent')
                ->body('This client has no email address, so they cannot set a password.')
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
