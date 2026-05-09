<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ContactMessage;

class QuoteRequestForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $message = '';
    public bool $submitted = false;

    protected $rules = [
        'name'    => 'required|string|max:255',
        'email'   => 'required|email|max:255',
        'phone'   => 'nullable|string|max:50',
        'message' => 'required|string|max:2000',
    ];

    protected $messages = [
        'name.required'    => 'Please enter your name.',
        'email.required'   => 'Please enter your email.',
        'email.email'      => 'Please enter a valid email address.',
        'message.required' => 'Please describe your shipment.',
    ];

    public function submit()
    {
        $this->validate();

        ContactMessage::create([
            'name'    => $this->name,
            'email'   => $this->email,
            'phone'   => $this->phone ?: null,
            'subject' => 'Quote Request',
            'message' => $this->message,
            'status'  => 'pending',
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
