<?php

namespace Tests\Feature;

use App\Livewire\QuoteRequestForm;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteRequestFormSmsOptInTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_without_a_phone_number_does_not_require_sms_opt_in(): void
    {
        Livewire::test(QuoteRequestForm::class)
            ->set('name', 'Ama Owusu')
            ->set('email', 'ama@example.com')
            ->set('message', 'Need a quote for a container shipment.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(ContactMessage::class, [
            'email' => 'ama@example.com',
            'phone' => null,
            'sms_opt_in' => false,
        ]);
    }

    public function test_submission_with_a_phone_number_requires_sms_opt_in(): void
    {
        Livewire::test(QuoteRequestForm::class)
            ->set('name', 'Ama Owusu')
            ->set('email', 'ama@example.com')
            ->set('phone', '+233509725081')
            ->set('message', 'Need a quote for a container shipment.')
            ->call('submit')
            ->assertHasErrors('smsOptIn');

        $this->assertDatabaseMissing(ContactMessage::class, [
            'email' => 'ama@example.com',
        ]);
    }

    public function test_submission_with_a_phone_number_and_sms_opt_in_succeeds(): void
    {
        Livewire::test(QuoteRequestForm::class)
            ->set('name', 'Ama Owusu')
            ->set('email', 'ama@example.com')
            ->set('phone', '+233509725081')
            ->set('smsOptIn', true)
            ->set('message', 'Need a quote for a container shipment.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(ContactMessage::class, [
            'email' => 'ama@example.com',
            'phone' => '+233509725081',
            'sms_opt_in' => true,
        ]);
    }
}
