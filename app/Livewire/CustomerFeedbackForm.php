<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Shipment;
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
    public string $type = 'feedback';
    public string $tracking_number = '';
    public bool $submitted = false;

    // Pre-linked complaint token
    public ?string $complaintToken = null;
    public ?string $shipmentId = null;

    protected $rules = [
        'customer_name' => 'required|string|max:255',
        'customer_email' => 'nullable|email|max:255',
        'customer_phone' => 'required|string|max:50',
        'feedback_on' => 'required|in:Rose Shipment,NeoRide Africa',
        'category' => 'required|string',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string',
        'invoice_number' => 'nullable|string|max:255',
        'type' => 'required|in:feedback,complaint',
        'tracking_number' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'customer_name.required' => 'Please enter your name.',
        'customer_email.email' => 'Please enter a valid email address.',
        'customer_phone.required' => 'Please enter your phone number.',
        'feedback_on.required' => 'Please select a service.',
        'category.required' => 'Please select a feedback category.',
        'rating.required' => 'Please select a rating.',
    ];

    public function mount(?string $token = null)
    {
        if ($token) {
            $feedback = CustomerFeedback::where('complaint_token', $token)->first();
            if ($feedback) {
                $this->complaintToken = $token;
                $this->type = 'complaint';
                $this->shipmentId = $feedback->shipment_id;
                $this->customer_name = $feedback->customer_name !== 'Pending' ? $feedback->customer_name : '';
                $this->customer_email = $feedback->customer_email ?? '';
                $this->customer_phone = $feedback->customer_phone ?? '';

                if ($feedback->shipment) {
                    $this->tracking_number = $feedback->shipment->tracking_number ?? '';
                    $this->invoice_number = $feedback->shipment->shipping_reference ?? '';
                }
            }
        }
    }

    public function lookupShipment()
    {
        if (!empty($this->tracking_number)) {
            $shipment = Shipment::where('tracking_number', $this->tracking_number)
                ->orWhere('shipping_reference', $this->tracking_number)
                ->first();

            if ($shipment) {
                $this->shipmentId = $shipment->id;
                $this->invoice_number = $shipment->shipping_reference;
            }
        }
    }

    public function submit()
    {
        $this->validate();

        if ($this->complaintToken) {
            // Update existing complaint record
            $feedback = CustomerFeedback::where('complaint_token', $this->complaintToken)->first();
            if ($feedback) {
                $feedback->update([
                    'customer_name' => $this->customer_name,
                    'customer_email' => $this->customer_email,
                    'customer_phone' => $this->customer_phone,
                    'feedback_on' => $this->feedback_on,
                    'category' => $this->category,
                    'invoice_number' => $this->invoice_number ?: null,
                    'rating' => $this->rating,
                    'comment' => $this->comment ?: null,
                    'type' => $this->type,
                ]);
            }
        } else {
            // Create new feedback/complaint
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
                'type' => $this->type,
                'shipment_id' => $this->shipmentId,
            ]);
        }

        $this->submitted = true;
    }

    public function submitAnother()
    {
        $this->reset();
        $this->feedback_on = 'Rose Shipment';
        $this->category = 'Delivery Speed';
        $this->rating = 5;
        $this->type = 'feedback';
    }

    public function render()
    {
        return view('livewire.customer-feedback-form', [
            'feedbackSources' => CustomerFeedback::FEEDBACK_SOURCES,
            'categories' => CustomerFeedback::CATEGORIES,
            'types' => CustomerFeedback::TYPES,
        ]);
    }
}
