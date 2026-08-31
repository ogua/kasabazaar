<?php

namespace App\Filament\Tables\Actions;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Bulk-assign one MSC booking / bill-of-lading number to every selected
 * shipment. Each changed shipment fires ShipmentObserver, which stamps
 * `msc_tracking_updated_at` and emails + SMSes the client a live MSC link.
 *
 * Shared by the admin and client shipment lists.
 */
class UpdateMscTrackingBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'update_msc_tracking';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Update MSC Tracking')
            ->icon('heroicon-o-globe-alt')
            ->color('info')
            ->modalHeading('Update MSC Tracking Number')
            ->modalDescription('Applies the same MSC booking / bill-of-lading number to every selected shipment. Each client is emailed and SMSed a live MSC tracking link.')
            ->form([
                TextInput::make('msc_tracking_number')
                    ->label('MSC Tracking Number')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (Collection $records, array $data): void {
                $number = trim($data['msc_tracking_number']);
                $changed = 0;

                foreach ($records as $record) {
                    if ($record->msc_tracking_number === $number) {
                        continue;
                    }

                    $record->update(['msc_tracking_number' => $number]);
                    $changed++;
                }

                Notification::make()
                    ->title('MSC tracking updated')
                    ->body("{$changed} shipment(s) updated. Unchanged shipments were skipped.")
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
