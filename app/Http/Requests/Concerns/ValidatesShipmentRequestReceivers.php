<?php

namespace App\Http\Requests\Concerns;

use App\Models\ShipmentRequest;
use Illuminate\Support\Carbon;

/**
 * The receivers-and-items structure of a shipment (pickup) request, shared by the
 * public pickup-link form and the customer app so both submit the same shape.
 * Item values are never taken from the client: catalogue items are priced from
 * the product, typed-in items are priced by staff.
 */
trait ValidatesShipmentRequestReceivers
{
    /**
     * @return array<string, mixed>
     */
    protected function shipmentRequestRules(): array
    {
        return [
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
    protected function shipmentRequestMessages(): array
    {
        return [
            'receivers.required' => 'Add at least one receiver.',
            'receivers.max' => 'A request can have up to 20 receivers.',
            'receivers.*.name.required' => 'Each receiver needs a name.',
            'receivers.*.phone.required' => 'Each receiver needs a phone number.',
            'receivers.*.items.required' => 'Each receiver needs at least one item.',
            'receivers.*.items.max' => 'A receiver can have up to 50 items.',
            'receivers.*.items.*.product_id.required_without' => 'Choose a product for every item, or add it as a new product.',
            'receivers.*.items.*.product_id.exists' => 'One of the selected products is no longer available.',
            'receivers.*.items.*.description.required_without' => 'Enter the name of every new product.',
            'receivers.*.items.*.quantity.required' => 'Enter a quantity for every item.',
            'pickup_location.required' => 'Tell us where we should pick up your items.',
            'preferred_pickup_at.required' => 'Choose a preferred pickup date and time.',
            'preferred_pickup_at.after' => 'The pickup date must be in the future.',
        ];
    }

    /**
     * The validated request data, with item values stripped (the server prices
     * items) and the pickup time converted to the app's timezone.
     *
     * @return array{receivers: array<int, array<string, mixed>>, pickup_location: string, preferred_pickup_at: string, notes: ?string}
     */
    public function shipmentRequestData(): array
    {
        $receivers = collect($this->validated('receivers'))->map(function (array $receiver): array {
            $receiver['items'] = collect($receiver['items'])
                ->map(fn (array $item): array => collect($item)->except('estimated_value')->all())
                ->all();

            return $receiver;
        })->all();

        return [
            'receivers' => ShipmentRequest::normaliseReceivers($receivers),
            'pickup_location' => $this->validated('pickup_location'),
            'preferred_pickup_at' => Carbon::parse($this->validated('preferred_pickup_at'))
                ->setTimezone(config('app.timezone'))
                ->toDateTimeString(),
            'notes' => $this->validated('notes'),
        ];
    }
}
