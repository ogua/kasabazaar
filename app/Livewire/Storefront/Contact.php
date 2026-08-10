<?php

namespace App\Livewire\Storefront;

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Contact extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|max:2000')]
    public string $message = '';

    public bool $sent = false;

    public string $error = '';

    public function submit(): void
    {
        $this->validate();

        try {
            Mail::to(config('mail.from.address'))->send(new ContactMessageMail(
                senderName: $this->name,
                senderEmail: $this->email,
                body: $this->message,
            ));

            $this->sent = true;
            $this->reset(['name', 'email', 'message']);
        } catch (\Throwable $e) {
            Log::error('storefront.contact: failed to send contact message', ['message' => $e->getMessage()]);
            $this->error = 'We couldn\'t send your message right now. Please try again shortly or email us directly.';
        }
    }

    public function render()
    {
        return view('livewire.storefront.contact')->layout('storefront.layouts.app', ['title' => 'Contact Us']);
    }
}
