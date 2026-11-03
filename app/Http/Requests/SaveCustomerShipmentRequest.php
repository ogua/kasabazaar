<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesShipmentRequestReceivers;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A shipment (pickup) request created or edited by a signed-in client in the app.
 * Same structure as the public pickup-link form.
 */
class SaveCustomerShipmentRequest extends FormRequest
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
        return $this->shipmentRequestRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->shipmentRequestMessages();
    }
}
