<?php

namespace App\Http\Requests;

use App\Enums\ShippingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BulkContainerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', new Enum(ShippingStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
            'notify_clients' => ['boolean'],
        ];
    }
}
