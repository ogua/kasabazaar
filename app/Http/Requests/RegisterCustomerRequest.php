<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Client signup from the customer app (and the marketplace). Phone is optional;
 * when given it is used to find a client record staff already created.
 */
class RegisterCustomerRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'whatsapp_opt_in' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists. Sign in instead.',
            'phone.regex' => 'Please enter a valid phone number.',
            'branch_id.exists' => 'Please choose a branch from the list.',
        ];
    }
}
