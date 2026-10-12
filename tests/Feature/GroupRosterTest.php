<?php

namespace Tests\Feature;

use Tests\TestCase;

class GroupRosterTest extends TestCase
{
    public function test_the_group_is_led_by_kasarose_and_no_longer_lists_kasabazaar(): void
    {
        $this->assertSame('KASAROSE Group of Companies', config('group.parent.name'));
        $this->assertNotContains('KasaBazaar', array_column(config('group.companies'), 'name'));
        $this->assertStringNotContainsString('kasabazaar.com', config('group.parent.url'));
    }

    public function test_the_privacy_policy_and_terms_name_the_kasarose_group_and_its_companies(): void
    {
        foreach (['privacy-policy', 'terms'] as $route) {
            $response = $this->get(route($route));

            $response->assertOk()
                ->assertSee('KASAROSE Group of Companies')
                ->assertDontSee('KasaBazaar Group')
                ->assertDontSee('<strong>KasaBazaar</strong> —', false);

            foreach (config('group.companies') as $company) {
                $response->assertSee($company['name']);
            }
        }
    }
}
