<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesShipmentRequestReceivers;
use App\Models\ShipmentRequestLink;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A pickup request submitted through a public, no-login link. The link token is
 * the only credential; sender details are asked for only when the link is not
 * already tied to a client.
 */
class StorePublicPickupRequest extends FormRequest
{
    use ValidatesShipmentRequestReceivers;

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
            ...$this->shipmentRequestRules(),
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
            ...$this->shipmentRequestMessages(),
        ];
    }

    public function link(): ?ShipmentRequestLink
    {
        return ShipmentRequestLink::where('token', $this->route('token'))->first();
    }
}
