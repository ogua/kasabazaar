<?php

namespace App\Filament\Pages;

use App\Service\SystemSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SmsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'SMS Settings';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.sms-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'sms_driver' => SystemSetting::get('sms_driver', config('services.sms.default', 'twilio')),
            'twilio_sid' => SystemSetting::get('twilio_sid', config('services.twilio.sid')),
            'twilio_token' => SystemSetting::get('twilio_token', config('services.twilio.token')),
            'twilio_from' => SystemSetting::get('twilio_from', config('services.twilio.from')),
            'mnotify_key' => SystemSetting::get('mnotify_key', config('services.mnotify.key')),
            'mnotify_sender' => SystemSetting::get('mnotify_sender', config('services.mnotify.sender', 'RDDSHIP')),
            'arkesel_key' => SystemSetting::get('arkesel_key', config('services.arkesel.key')),
            'arkesel_sender' => SystemSetting::get('arkesel_sender', config('services.arkesel.sender', 'RDDSHIP')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('SMS Provider')
                    ->description('Alerts route automatically by destination number: Ghana numbers (+233… or a local 0XXXXXXXXX) go through Arkesel, all other numbers go through Twilio. The provider below is only used for numbers that cannot be classified.')
                    ->schema([
                        Select::make('sms_driver')
                            ->label('Fallback SMS Provider')
                            ->options([
                                'twilio' => 'Twilio',
                                'arkesel' => 'Arkesel',
                                'mnotify' => 'MNotify',
                            ])
                            ->default('twilio')
                            ->required(),
                    ]),

                Section::make('Arkesel Configuration')
                    ->description('Credentials from your Arkesel account (arkesel.com). Used for Ghana numbers.')
                    ->schema([
                        TextInput::make('arkesel_key')
                            ->label('API Key')
                            ->password()
                            ->revealable(),
                        TextInput::make('arkesel_sender')
                            ->label('Sender ID')
                            ->placeholder('RDDSHIP')
                            ->maxLength(11),
                    ])
                    ->columns(1),

                Section::make('Twilio Configuration')
                    ->description('Credentials from your Twilio Console (console.twilio.com).')
                    ->schema([
                        TextInput::make('twilio_sid')
                            ->label('Account SID')
                            ->placeholder('ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                            ->password()
                            ->revealable(),
                        TextInput::make('twilio_token')
                            ->label('Auth Token')
                            ->password()
                            ->revealable(),
                        TextInput::make('twilio_from')
                            ->label('From Number')
                            ->placeholder('+12025551234'),
                    ])
                    ->columns(1),

                Section::make('MNotify Configuration')
                    ->description('Credentials from your MNotify account.')
                    ->schema([
                        TextInput::make('mnotify_key')
                            ->label('API Key (V2)')
                            ->password()
                            ->revealable(),
                        TextInput::make('mnotify_sender')
                            ->label('Sender ID')
                            ->placeholder('RDDSHIP'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            SystemSetting::set($key, $value, 'sms');
        }

        Notification::make()
            ->title('SMS settings saved successfully.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->color('primary'),
        ];
    }
}
