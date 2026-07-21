<?php

namespace App\Filament\Pages;

use App\Enums\UserStatus;
use App\Models\User;
use App\Service\ImpersonationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImpersonateUser extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?string $navigationLabel = 'Impersonate User';

    protected static string $view = 'filament.pages.impersonate-user';

    public static function canAccess(): bool
    {
        return ImpersonationService::canImpersonate();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()->where('id', '!=', auth()->id()))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('impersonate')
                    ->label('Login As')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('You will be logged in as this user until you stop impersonating.')
                    ->visible(fn () => ! ImpersonationService::isImpersonating())
                    ->action(function (User $record) {
                        if (! ImpersonationService::canImpersonate()) {
                            abort(403);
                        }

                        if ($record->status !== UserStatus::Active) {
                            Notification::make()
                                ->title('Cannot impersonate an inactive user')
                                ->danger()
                                ->send();

                            return;
                        }

                        return redirect()->to(ImpersonationService::startUrl($record));
                    }),
            ]);
    }
}
