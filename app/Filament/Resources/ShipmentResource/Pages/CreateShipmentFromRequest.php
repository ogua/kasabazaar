<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentRequestResource;
use App\Models\Shipment;
use App\Models\ShipmentRequest;
use App\Services\LocationLookup;
use App\Services\ShipmentRequestApprovalService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

/**
 * Converts a client's shipment request with the full create-shipment wizard,
 * pre-filled from the request, so staff check and complete everything (route,
 * receivers, items, payments, insurance, dates) in the form they already know.
 * Saving creates the shipment exactly like a new one, then marks the request
 * approved and tells the client.
 */
class CreateShipmentFromRequest extends CreateShipment
{
    public string $shipmentRequestId;

    public function mount(?string $shipmentRequest = null): void
    {
        $request = ShipmentRequest::with('client')->findOrFail($shipmentRequest);

        if (! $request->isEditable() || $request->shipment_id) {
            Notification::make()
                ->title('This request has already been dealt with')
                ->body('It was approved or rejected, so it can no longer be converted.')
                ->warning()
                ->send();

            $this->redirect(ShipmentRequestResource::getUrl('view', ['record' => $request]));

            return;
        }

        $this->shipmentRequestId = $request->id;

        parent::mount();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Convert Request to Shipment';
    }

    public function getBreadcrumb(): string
    {
        return 'Convert request';
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        // Fill the wizard's defaults first, then lay the request over them. The form is
        // rebuilt in between: a repeater remembers it was given defaults and would
        // otherwise put its one blank receiver back over the request's receivers.
        $this->form->fill();
        $defaults = $this->form->getRawState();

        $this->cacheForms();
        $this->form->fill([...$defaults, ...$this->requestFormData($this->shipmentRequest())]);

        $this->callHook('afterFill');

        $this->warnAboutItemsWithoutProduct();
    }

    /**
     * Two staff converting the same request at once must not make two shipments.
     */
    protected function beforeCreate(): void
    {
        $request = $this->shipmentRequest();

        if ($request->shipment_id || ! $request->isEditable()) {
            Notification::make()
                ->title('This request was converted by someone else')
                ->body('No new shipment was created.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        parent::afterCreate();

        /** @var Shipment $shipment */
        $shipment = $this->getRecord();

        app(ShipmentRequestApprovalService::class)->markConverted($this->shipmentRequest(), $shipment, Auth::user());
    }

    private function shipmentRequest(): ShipmentRequest
    {
        return ShipmentRequest::with('client')->findOrFail($this->shipmentRequestId);
    }

    /**
     * The wizard's state for a request: the client, the request notes, and each
     * receiver with their items. Locations typed as text are matched to the
     * country/region/city lists; a phone's dialling code fills a missing country.
     * Item values are line totals (unit value × quantity) like the wizard keeps them.
     *
     * @return array<string, mixed>
     */
    private function requestFormData(ShipmentRequest $request): array
    {
        $receivers = collect($request->receivers ?? [])->map(function (array $receiver): array {
            $countryId = LocationLookup::countryIdForName($receiver['country'] ?? null)
                ?? LocationLookup::countryIdForPhone($receiver['phone'] ?? null);
            $stateId = LocationLookup::stateIdForName($countryId, $receiver['state_region'] ?? null);

            return [
                'receiver_name' => $receiver['name'] ?? null,
                'receiver_phone' => $receiver['phone'] ?? null,
                'country' => $countryId,
                'state_region' => $stateId,
                'city' => LocationLookup::cityIdForName($stateId, $receiver['city'] ?? null),
                'address' => filled($receiver['address'] ?? null) ? $receiver['address'] : null,
                'items' => collect($receiver['items'] ?? [])->map(fn (array $item): array => [
                    'box_no' => null,
                    'product_id' => $item['product_id'] ?? null,
                    'requested_name' => blank($item['product_id'] ?? null) ? ($item['description'] ?? null) : null,
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'item_cost' => round((float) ($item['estimated_value'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1)), 2),
                    'is_vehicle' => false,
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'shipment_type' => 'new',
            'client_id' => $request->client_id,
            'client_existence' => Shipment::where('client_id', $request->client_id)->exists() ? 'returning-client' : 'new-client',
            'receivers' => $receivers,
            'client_note' => $request->notes,
        ];
    }

    /**
     * Items the client typed as a new product have no catalogue product yet; the
     * wizard needs one, so say which before staff reach the items step.
     */
    private function warnAboutItemsWithoutProduct(): void
    {
        $missing = collect($this->shipmentRequest()->receivers ?? [])
            ->flatMap(fn (array $receiver) => collect($receiver['items'] ?? [])
                ->filter(fn (array $item): bool => blank($item['product_id'] ?? null))
                ->map(fn (array $item): string => trim(($item['description'] ?? 'Unnamed item').' (for '.($receiver['name'] ?? 'receiver').')')))
            ->values();

        if ($missing->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Pick or add a product for '.$missing->count().' item(s)')
            ->body('The client asked for products that are not in the catalogue yet: '.$missing->implode(', ').'. On the Items step, each of those rows says what the client asked for: pick a matching product, or click + beside Product (the name is filled in) and save it.')
            ->warning()
            ->persistent()
            ->send();
    }
}
