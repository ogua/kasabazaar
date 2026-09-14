<?php

namespace App\Livewire;

use App\Models\ContactMessage;
use Illuminate\Validation\Rule;
use Livewire\Component;

class QuoteRequestForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public bool $smsOptIn = false;

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'required|string|max:2000',
            'smsOptIn' => [Rule::requiredIf($this->phone !== ''), 'boolean'],
        ];
    }

    protected $messages = [
        'name.required' => 'Please enter your name.',
        'email.required' => 'Please enter your email.',
        'email.email' => 'Please enter a valid email address.',
        'message.required' => 'Please describe your shipment.',
        'smsOptIn.required' => 'Please confirm you agree to receive SMS updates, or leave the phone number blank.',
    ];

    public function submit()
    {
        $this->validate();

        if ($this->phone !== '' && ! $this->smsOptIn) {
            $this->addError('smsOptIn', 'Please confirm you agree to receive SMS updates, or leave the phone number blank.');

            return;
        }

        ContactMessage::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'sms_opt_in' => $this->phone !== '' && $this->smsOptIn,
            'subject' => 'Quote Request',
            'message' => $this->message,
            'status' => 'pending',
        ]);

        $this->submitted = true;
    }

    public function submitAnother()
    {
        $this->reset();
    }

    public function render()
    {
        return view('livewire.quote-request-form');
    }
}
