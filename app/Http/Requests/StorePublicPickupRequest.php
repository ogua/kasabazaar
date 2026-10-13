<?php

namespace App\Http\Requests;

use App\Models\ShipmentRequestLink;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A pickup request submitted through a public, no-login link. The link token is
 * the only credential; sender details are asked for only when the link is not
 * already tied to a client.
 */
class StorePublicPickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $needsSender = ! $this->link()?->client_id;

        return [
            'sender_name' => [$needsSender ? 'required' : 'nullable', 'string', 'max:255'],
            'sender_phone' => [$needsSender ? 'required' : 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'sender_email' => ['nullable', 'email', 'max:255'],
            'receivers' => 'required|array|min:1|max:20',
            'receivers.*.name' => 'required|string|max:255',
            'receivers.*.phone' => 'required|string|max:30',
            'receivers.*.country' => 'nullable|string|max:100',
            'receivers.*.state_region' => 'nullable|string|max:100',
            'receivers.*.city' => 'nullable|string|max:100',
            'receivers.*.address' => 'nullable|string|max:500',
            'receivers.*.items' => 'required|array|min:1|max:50',
            'receivers.*.items.*.product_id' => 'nullable|required_without:receivers.*.items.*.description|uuid|exists:products,id',
            'receivers.*.items.*.description' => 'nullable|required_without:receivers.*.items.*.product_id|string|max:255',
            'receivers.*.items.*.details' => 'nullable|string|max:500',
            'receivers.*.items.*.quantity' => 'required|integer|min:1|max:10000',
            'pickup_location' => 'required|string|max:255',
            'preferred_pickup_at' => 'required|date|after:now',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sender_name.required' => 'Please enter your full name.',
            'sender_phone.required' => 'Please enter a phone number we can reach you on.',
            'sender_phone.regex' => 'Please enter a valid phone number.',
            'receivers.required' => 'Add at least one receiver.',
            'receivers.*.name.required' => 'Each receiver needs a name.',
            'receivers.*.phone.required' => 'Each receiver needs a phone number.',
            'receivers.*.items.required' => 'Each receiver needs at least one item.',
            'receivers.*.items.*.product_id.required_without' => 'Choose a product for every item, or add it as a new product.',
            'receivers.*.items.*.product_id.exists' => 'One of the selected products is no longer available.',
            'receivers.*.items.*.description.required_without' => 'Enter the name of every new product.',
            'receivers.*.items.*.quantity.required' => 'Enter a quantity for every item.',
            'pickup_location.required' => 'Tell us where we should pick up your items.',
            'preferred_pickup_at.required' => 'Choose a preferred pickup date and time.',
            'preferred_pickup_at.after' => 'The pickup date must be in the future.',
        ];
    }

    public function link(): ?ShipmentRequestLink
    {
        return ShipmentRequestLink::where('token', $this->route('token'))->first();
    }
}
