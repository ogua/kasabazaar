<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * A contact, quote or feedback form posted from the public website. The forms
 * submit by AJAX (public/site/js/form.js), which shows the response body as-is,
 * so a validation failure answers with the first message as plain text.
 */
class StoreWebsiteEnquiryRequest extends FormRequest
{
    /**
     * The extra quote/feedback fields a form may post, mapped to the label staff read.
     * Anything not listed here is ignored rather than stored.
     *
     * @var array<string, string>
     */
    public const DETAIL_FIELDS = [
        'city' => 'City',
        'ftype' => 'Service Type',
        'wight' => 'Weight',
        'dymention' => 'Dimensions',
        'departure' => 'Departure',
        'city_deliver' => 'Delivery',
        'incoterms' => 'Incoterms',
        'ship_date' => 'Ship Date',
        'ship_time' => 'Ship Time',
        'tracking_ref' => 'Tracking Number / Reference',
        'feedback_about' => 'Feedback About',
        'feedback_category' => 'Category',
        'rating' => 'Rating (1-5)',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isFeedback = $this->input('form_type') === 'feedback';

        $detailRules = collect(self::DETAIL_FIELDS)
            ->map(fn (): array => ['nullable', 'string', 'max:255'])
            ->all();

        return [
            ...$detailRules,
            'form_type' => ['required', Rule::in(['contact', 'quote', 'feedback'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'message' => [$isFeedback ? 'required' : 'nullable', 'string', 'max:5000'],
            'sms_opt_in' => ['nullable', 'boolean'],
            'rating' => [$isFeedback ? 'required' : 'nullable', 'integer', 'between:1,5'],
            'feedback_category' => ['nullable', 'string', Rule::in(config('website.feedback_categories'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'form_type.*' => 'Sorry, this form could not be sent. Please refresh the page and try again.',
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Please enter a phone number we can reach you on.',
            'message.required' => 'Please tell us what happened.',
            'rating.required' => 'Please rate your experience from 1 to 5.',
            'rating.*' => 'Please rate your experience from 1 to 5.',
            'feedback_category.in' => 'Please choose a category from the list.',
        ];
    }

    /**
     * The submitted detail fields that have a value, as label => value.
     *
     * @return array<string, string>
     */
    public function details(): array
    {
        return collect(self::DETAIL_FIELDS)
            ->mapWithKeys(fn (string $label, string $field): array => [$label => trim((string) $this->validated($field))])
            ->filter(fn (string $value): bool => $value !== '')
            ->all();
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response($validator->errors()->first(), 422)->header('Content-Type', 'text/plain; charset=UTF-8')
        );
    }
}
