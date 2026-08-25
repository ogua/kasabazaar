<?php

namespace App\Filament\Resources\InvestorResource\Pages;

use App\Filament\Resources\InvestorResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

        $user = User::create([
            'name' => $record->name,
            'email' => $record->email,
            'phone' => $record->phone,
            // Random and never disclosed. The investor claims the account via the
            // reset link below — a shared default password would expose capital
            // balances, withdrawal requests and conversions to anyone who guessed it.
            'password' => Hash::make(Str::random(48)),
            'role' => 'investor',
            'investor_id' => $record->id,
        ]);

        $invited = true;

        try {
            Password::broker()->sendResetLink(['email' => $record->email]);
        } catch (\Throwable $e) {
            report($e);
            $invited = false;
        }

        Notification::make()
            ->title('Portal login created')
            ->body($invited
                ? "A password set-up link has been emailed to {$record->email}."
                : "The login was created, but the set-up email to {$record->email} could not be sent. Ask the investor to use Forgot Password.")
            ->{$invited ? 'success' : 'warning'}()
            ->send();
    }
}
