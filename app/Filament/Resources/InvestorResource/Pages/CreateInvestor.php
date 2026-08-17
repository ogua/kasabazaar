<?php

namespace App\Filament\Resources\InvestorResource\Pages;

use App\Filament\Resources\InvestorResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateInvestor extends CreateRecord
{
    protected static string $resource = InvestorResource::class;

    public function afterCreate(): void
    {
        $record = $this->record;

        if (! $record->email) {
            return;
        }

        if (User::where('email', $record->email)->exists()) {
            Notification::make()
                ->title('Portal login not created')
                ->body("A user already exists with the email {$record->email}. Link an existing user to this investor from the Users page if needed.")
                ->warning()
                ->send();

            return;
        }

        User::create([
            'name' => $record->name,
            'email' => $record->email,
            'phone' => $record->phone,
            'password' => Hash::make('password'),
            'role' => 'investor',
            'investor_id' => $record->id,
        ]);

        Notification::make()
            ->title('Portal login created')
            ->body("A default login was created for {$record->email} with password \"password\". Advise the investor to change it after first login.")
            ->success()
            ->send();
    }
}
