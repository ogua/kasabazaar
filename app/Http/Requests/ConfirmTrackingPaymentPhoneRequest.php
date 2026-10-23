<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The phone check that stands between a tracking number and the shipment's
 * balance on the public tracking page.
 */
class ConfirmTrackingPaymentPhoneRequest extends FormRequest
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
        return [
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[\d\s\-().]{9,}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Enter the phone number we have on file for this shipment.',
            'phone.regex' => 'Enter a full phone number, including the area or network code.',
        ];
    }
}
