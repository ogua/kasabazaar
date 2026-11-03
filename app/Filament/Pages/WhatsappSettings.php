<?php

namespace App\Filament\Pages;

use App\Service\SystemSetting;
use App\Services\Whatsapp\WhatsappGateway;
use App\Services\Whatsapp\WhatsappGatewayException;
use App\Services\Whatsapp\WhatsappSettings as GatewaySettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * Connects this app to its WhatsApp provider — Infobip by default, or the Ogua
 * gateway (KASAROSE's partner account) — and maps each notification event to
 * an approved template per sender
 * (logistics, marketplace). Recipients without a mapped template or WhatsApp
 * consent keep getting SMS; email is unaffected.
 */
class WhatsappSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'WhatsApp Settings';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.whatsapp-settings';

    public ?array $data = [];

    /**
     * Approved templates fetched from the gateway, per sender.
     *
     * @var array<string, list<array<string, mixed>>>
     */
    public array $gatewayTemplates = [];

    public ?string $gatewayError = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $data = [
            'whatsapp_enabled' => (bool) SystemSetting::get('whatsapp_enabled', false),
            'whatsapp_provider' => GatewaySettings::provider(),
            'infobip_base_url' => GatewaySettings::infobipBaseUrl(),
            'infobip_whatsapp_key' => GatewaySettings::infobipApiKey(),
            'infobip_whatsapp_number_logistics' => GatewaySettings::infobipNumber(GatewaySettings::SENDER_LOGISTICS),
            'infobip_whatsapp_number_marketplace' => SystemSetting::get('infobip_whatsapp_number_marketplace') ?: config('services.infobip.whatsapp_number_marketplace'),
            'whatsapp_gateway_url' => SystemSetting::get('whatsapp_gateway_url'),
            'whatsapp_client_key' => SystemSetting::get('whatsapp_client_key'),
            'whatsapp_client_secret' => SystemSetting::get('whatsapp_client_secret'),
            'whatsapp_callback_secret' => SystemSetting::get('whatsapp_callback_secret'),
            'whatsapp_test_numbers' => SystemSetting::get('whatsapp_test_numbers'),
        ];

        foreach (GatewaySettings::EVENTS as $sender => $events) {
            foreach (array_keys($events) as $event) {
                $key = GatewaySettings::templateSettingKey($sender, $event);
                $data[$key] = SystemSetting::get($key);
            }
        }

        $this->form->fill($data);
        $this->loadGatewayTemplates();
    }

    protected function loadGatewayTemplates(): void
    {
        $this->gatewayTemplates = [];
        $this->gatewayError = null;

        if (! GatewaySettings::isConfigured()) {
            return;
        }

        try {
            $client = app(WhatsappGateway::class);

            foreach (array_keys(GatewaySettings::EVENTS) as $sender) {
                $this->gatewayTemplates[$sender] = $client->templates($sender);
            }
        } catch (WhatsappGatewayException $e) {
            $this->gatewayError = $e->getMessage();
        }
    }

    public function form(Form $form): Form
    {
        $sections = [
            Section::make('WhatsApp provider')
                ->schema([
                    Toggle::make('whatsapp_enabled')
                        ->label('Send WhatsApp notifications')
                        ->helperText('When off, every alert goes out by SMS and email exactly as before.'),
                    Select::make('whatsapp_provider')
                        ->label('Provider')
                        ->options([
                            GatewaySettings::PROVIDER_INFOBIP => 'Infobip',
                            GatewaySettings::PROVIDER_OGUA => 'Ogua WhatsApp gateway',
                        ])
                        ->default(GatewaySettings::PROVIDER_INFOBIP)
                        ->selectablePlaceholder(false)
                        ->live()
                        ->helperText('Template mappings below belong to the provider they were picked from — save, then re-map after switching.'),
                    Textarea::make('whatsapp_test_numbers')
                        ->label('Rollout: only these numbers')
                        ->helperText('Comma-separated international numbers. While set, only these numbers get WhatsApp; everyone else keeps SMS. Clear it to go live.')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Infobip connection')
                ->description('A WhatsApp-scoped API key from the Infobip portal (Developers → API keys) and the WhatsApp sender numbers registered on it.')
                ->visible(fn (Get $get): bool => $get('whatsapp_provider') !== GatewaySettings::PROVIDER_OGUA)
                ->schema([
                    TextInput::make('infobip_base_url')
                        ->label('API base URL')
                        ->url()
                        ->placeholder('https://api.infobip.com'),
                    TextInput::make('infobip_whatsapp_key')
                        ->label('API key')
                        ->password()
                        ->revealable(),
                    TextInput::make('infobip_whatsapp_number_logistics')
                        ->label('Logistics WhatsApp number')
                        ->placeholder('447860088970'),
                    TextInput::make('infobip_whatsapp_number_marketplace')
                        ->label('Marketplace WhatsApp number')
                        ->placeholder('Same as logistics'),
                    Placeholder::make('infobip_webhook_url')
                        ->label('In the Infobip portal, set this as the sender\'s inbound-message and seen-report URL')
                        ->helperText('Delivery reports are requested on every message automatically. Inbound STOP / START replies opt numbers out / back in.')
                        ->content(fn (): HtmlString => new HtmlString('<code>'.e(GatewaySettings::infobipWebhookUrl()).'</code>'))
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Ogua gateway connection')
                ->description('API key and secret from the "API Keys" page of your Ogua WhatsApp partner portal.')
                ->visible(fn (Get $get): bool => $get('whatsapp_provider') === GatewaySettings::PROVIDER_OGUA)
                ->schema([
                    TextInput::make('whatsapp_gateway_url')
                        ->label('Gateway URL')
                        ->url()
                        ->placeholder('https://app.oguaschool.com'),
                    TextInput::make('whatsapp_client_key')
                        ->label('Client key')
                        ->password()
                        ->revealable(),
                    TextInput::make('whatsapp_client_secret')
                        ->label('Client secret')
                        ->password()
                        ->revealable(),
                    TextInput::make('whatsapp_callback_secret')
                        ->label('Callback signing secret')
                        ->helperText('From the "Callback URL" page of the partner portal.')
                        ->password()
                        ->revealable(),
                    Placeholder::make('callback_url')
                        ->label('Set this as your callback URL in the partner portal')
                        ->content(fn (): HtmlString => new HtmlString('<code>'.e(route('webhooks.whatsapp-gateway')).'</code>')),
                ])
                ->columns(2),
        ];

        foreach (GatewaySettings::EVENTS as $sender => $events) {
            $selects = [];

            foreach ($events as $event => $definition) {
                $selects[] = Select::make(GatewaySettings::templateSettingKey($sender, $event))
                    ->label($definition['label'])
                    ->options(fn (): array => $this->templateOptions($sender, count($definition['params'])))
                    ->placeholder('Not sent over WhatsApp (SMS instead)')
                    ->helperText('Template body values, in order: '.implode(', ', array_map(
                        fn (string $param, int $index): string => '{{'.($index + 1).'}} '.$param,
                        $definition['params'],
                        array_keys($definition['params'])
                    )));
            }

            $sections[] = Section::make(ucfirst($sender).' templates')
                ->description($this->gatewayError
                    ? 'Could not load templates from the gateway: '.$this->gatewayError
                    : "Only approved templates of the {$sender} number with the right number of body values are listed.")
                ->schema($selects)
                ->columns(2)
                ->collapsible();
        }

        return $form->schema($sections)->statePath('data');
    }

    /**
     * @return array<string, string> "template|language" => label
     */
    protected function templateOptions(string $sender, int $paramCount): array
    {
        $options = [];

        foreach ($this->gatewayTemplates[$sender] ?? [] as $template) {
            if ((int) ($template['body_param_count'] ?? -1) !== $paramCount) {
                continue;
            }

            $options[$template['template'].'|'.$template['language']] = "{$template['template']} ({$template['language']})";
        }

        return $options;
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            SystemSetting::set($key, is_bool($value) ? (int) $value : $value, 'whatsapp');
        }

        $this->loadGatewayTemplates();

        Notification::make()->title('WhatsApp settings saved.')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test connection')
                ->icon('heroicon-o-signal')
                ->action(function (): void {
                    try {
                        $senders = app(WhatsappGateway::class)->senders();
                    } catch (WhatsappGatewayException $e) {
                        Notification::make()->danger()->title('Gateway connection failed')->body($e->getMessage())->send();

                        return;
                    }

                    $lines = array_map(
                        fn (array $sender): string => "{$sender['sender']}: ".($sender['connected'] ? 'connected ('.($sender['display_phone_number'] ?? '').')' : 'not connected'),
                        $senders
                    );

                    Notification::make()->success()->title('Gateway reachable')->body(implode("\n", $lines) ?: 'No senders set up yet.')->send();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->color('primary'),
        ];
    }
}
