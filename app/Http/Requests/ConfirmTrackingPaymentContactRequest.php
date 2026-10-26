<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The check that stands between a tracking number and the shipment's balance on
 * the public tracking page: a phone number or email address on file.
 */
class ConfirmTrackingPaymentContactRequest extends FormRequest
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
            'contact' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->isEmail() && ! preg_match('/^\+?[\d\s\-().]{9,}$/', (string) $value)) {
                        $fail('Enter a full phone number (with the area or network code) or an email address.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact.required' => 'Enter the phone number or email address we have on file for this shipment.',
        ];
    }

    public function isEmail(): bool
    {
        return filter_var(trim((string) $this->input('contact')), FILTER_VALIDATE_EMAIL) !== false;
    }
}
