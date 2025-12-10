<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CustomerFeedback;

class CustomerFeedbackForm extends Component
{
    public string $customer_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';
    public string $feedback_on = 'Rose Shipment';
    public string $category = 'Delivery Speed';
    public string $invoice_number = '';
    public int $rating = 5;
    public string $comment = '';
    public bool $submitted = false;

    protected $rules = [
        'customer_name' => 'required|string|max:255',
        'customer_email' => 'nullable|email|max:255',
        'customer_phone' => 'required|string|max:50',
        'feedback_on' => 'required|in:Rose Shipment,NeoRide Africa',
        'category' => 'required|string',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string',
        'invoice_number' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'customer_name.required' => 'Please enter your name.',
        //'customer_email.required' => 'Please enter your email address.',
        'customer_email.email' => 'Please enter a valid email address.',
        'customer_phone.required' => 'Please enter your phone number.',
        'feedback_on.required' => 'Please select a service.',
        'category.required' => 'Please select a feedback category.',
        'rating.required' => 'Please select a rating.',
    ];

    public function submit()
    {
        $this->validate();

        CustomerFeedback::create([
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'feedback_on' => $this->feedback_on,
            'category' => $this->category,
            'invoice_number' => $this->invoice_number ?: null,
            'rating' => $this->rating,
            'comment' => $this->comment ?: null,
            'status' => 'pending',
        ]);

        $this->submitted = true;
    }

    public function submitAnother()
    {
        $this->reset();
        $this->feedback_on = 'Rose Shipment';
        $this->category = 'Delivery Speed';
        $this->rating = 5;
    }

    public function render()
    {
        return view('livewire.customer-feedback-form', [
            'feedbackSources' => CustomerFeedback::FEEDBACK_SOURCES,
            'categories' => CustomerFeedback::CATEGORIES,
        ]);
    }
}
