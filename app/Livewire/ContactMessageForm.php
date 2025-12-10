<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ContactMessage;

class ContactMessageForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $subject = '';
    public string $message = '';
    public bool $submitted = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
        'subject' => 'required|string|max:255',
        'message' => 'required|string',
    ];

    protected $messages = [
        'name.required' => 'Please enter your name.',
        'email.email' => 'Please enter a valid email address.',
        'subject.required' => 'Please enter a subject.',
        'message.required' => 'Please enter your message.',
    ];

    public function submit()
    {
        $this->validate();

        ContactMessage::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'subject' => $this->subject,
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
        return view('livewire.contact-message-form');
    }
}
