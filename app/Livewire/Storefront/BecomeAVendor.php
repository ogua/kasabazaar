<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\KasabazaarClient;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class BecomeAVendor extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $business_name = '';

    #[Validate('required|string|max:255')]
    public string $contact_name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $product_category = '';

    #[Validate('nullable|string|max:1000')]
    public string $message = '';

    #[Validate('required|file|max:5120')]
    public $business_certificate;

    #[Validate('required|image|max:5120')]
    public $ghana_card_front;

    #[Validate('required|image|max:5120')]
    public $ghana_card_back;

    #[Validate('nullable|file|max:5120')]
    public $proof_of_address;

    public bool $submitted = false;

    public string $error = '';

    public function submit(KasabazaarClient $client): void
    {
        $this->validate();

        $files = [
            ['name' => 'business_certificate', 'contents' => fopen($this->business_certificate->getRealPath(), 'r'), 'filename' => $this->business_certificate->getClientOriginalName()],
            ['name' => 'ghana_card_front', 'contents' => fopen($this->ghana_card_front->getRealPath(), 'r'), 'filename' => $this->ghana_card_front->getClientOriginalName()],
            ['name' => 'ghana_card_back', 'contents' => fopen($this->ghana_card_back->getRealPath(), 'r'), 'filename' => $this->ghana_card_back->getClientOriginalName()],
        ];

        if ($this->proof_of_address) {
            $files[] = ['name' => 'proof_of_address', 'contents' => fopen($this->proof_of_address->getRealPath(), 'r'), 'filename' => $this->proof_of_address->getClientOriginalName()];
        }

        try {
            $client->postMultipart('marketplace/vendor-applications', [
                'business_name' => $this->business_name,
                'contact_name' => $this->contact_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'product_category' => $this->product_category,
                'message' => $this->message,
            ], $files);

            $this->submitted = true;
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.storefront.become-a-vendor')->layout('storefront.layouts.app', ['title' => 'Become a Vendor']);
    }
}
