<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Starting a Paystack checkout from the public tracking page. The upper limit
 * (the outstanding balance) is checked by the controller, which has the shipment.
 */
class StoreTrackingPaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:1', 'decimal:0,2'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Enter the amount you want to pay.',
            'amount.min' => 'The smallest payment we can take online is $1.00.',
            'amount.decimal' => 'Enter the amount in dollars and cents, e.g. 150.00.',
            'email.required' => 'Enter an email address for your receipt.',
            'email.email' => 'Enter a valid email address for your receipt.',
        ];
    }
}
