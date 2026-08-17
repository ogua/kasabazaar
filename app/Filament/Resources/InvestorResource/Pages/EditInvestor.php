<?php

namespace App\Filament\Resources\InvestorResource\Pages;

use App\Filament\Resources\InvestorResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditInvestor extends EditRecord
{
    protected static string $resource = InvestorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function afterSave(): void
    {
        $record = $this->record;

        if (! $record->email || $record->users()->exists()) {
            return;
        }

        if (User::where('email', $record->email)->exists()) {
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
