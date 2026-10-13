<?php

namespace App\Filament\Resources\ShipmentRequestResource\Pages;

use App\Filament\Resources\ShipmentRequestResource;
use App\Models\ShipmentRequest;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Staff adjust a request (receivers, products, quantities, pickup details) before
 * approving it. Only submitted / under-review requests can be edited.
 */
class EditShipmentRequest extends EditRecord
{
    protected static string $resource = ShipmentRequestResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! $this->getRecord()->isEditable()) {
            Notification::make()
                ->title('Only submitted or under-review requests can be edited.')
                ->warning()
                ->send();

            $this->redirect(ShipmentRequestResource::getUrl('view', ['record' => $this->getRecord()]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['receivers'] = ShipmentRequest::normaliseReceivers($data['receivers'] ?? []);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ShipmentRequestResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
