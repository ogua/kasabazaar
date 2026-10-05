<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsappSettings;
use App\Models\User;
use App\Service\SystemSetting;
use App\Services\Whatsapp\WhatsappSettings as GatewaySettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsappSettingsPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        SystemSetting::set('whatsapp_gateway_url', 'https://gateway.test', 'whatsapp');
        SystemSetting::set('whatsapp_client_key', 'pck_live_key', 'whatsapp');
        SystemSetting::set('whatsapp_client_secret', 'secret', 'whatsapp');
    }

    public function test_it_lists_only_templates_with_the_events_parameter_count_and_saves_the_mapping(): void
    {
        Http::fake([
            'gateway.test/api/partner/v1/whatsapp/senders/logistics/templates' => Http::response(['data' => [
                ['template' => 'shipment_status_update', 'language' => 'en', 'body_param_count' => 4, 'has_url_button' => true],
                ['template' => 'too_short', 'language' => 'en', 'body_param_count' => 2, 'has_url_button' => false],
            ]]),
            'gateway.test/api/partner/v1/whatsapp/senders/marketplace/templates' => Http::response(['data' => []]),
        ]);

        $statusKey = GatewaySettings::templateSettingKey('logistics', 'status');

        $page = Livewire::test(WhatsappSettings::class)->assertOk();

        $options = $page->instance()->form->getComponent("data.{$statusKey}")->getOptions();
        $this->assertSame(['shipment_status_update|en' => 'shipment_status_update (en)'], $options);

        $page->set("data.{$statusKey}", 'shipment_status_update|en')
            ->set('data.whatsapp_enabled', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['template' => 'shipment_status_update', 'language' => 'en'], GatewaySettings::templateFor('logistics', 'status'));
        $this->assertTrue(GatewaySettings::enabled());
    }

    public function test_the_page_still_loads_when_the_gateway_is_unreachable(): void
    {
        Http::fake(['gateway.test/*' => Http::response(['message' => 'Invalid signature.'], 401)]);

        Livewire::test(WhatsappSettings::class)
            ->assertOk()
            ->assertSet('gatewayError', 'Invalid signature.');
    }
}
