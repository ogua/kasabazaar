<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('manage_container')
                ->label('Container Status')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('warning')
                ->modalHeading('Manage Container Clearance Status')
                ->modalDescription('Select a container to update its clearance status and add review notes.')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Select::make('container_number')
                        ->label('Select Container')
                        ->options(function (): array {
                            $containers = Shipment::whereNotNull('container_number')
                                ->select('container_number')
                                ->distinct()
                                ->orderByDesc('container_number')
                                ->pluck('container_number');

                            return $containers->mapWithKeys(function (int $cn): array {
                                $count  = Shipment::where('container_number', $cn)->count();
                                $status = ShipmentContainer::where('container_number', $cn)
                                    ->value('is_cleared');
                                $badge  = $status ? ' — Cleared' : ' — Pending';
                                return [$cn => "CON{$cn}{$badge} ({$count} shipments)"];
                            })->toArray();
                        })
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?int $state, Forms\Set $set): void {
                            if (! $state) {
                                return;
                            }
                            $container = ShipmentContainer::where('container_number', $state)->first();
                            $set('is_cleared', $container?->is_cleared ?? false);
                            $set('review', $container?->review ?? '');
                        }),

                    Forms\Components\Toggle::make('is_cleared')
                        ->label('Container Cleared at Customs')
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('Toggle ON when this container has been cleared at customs.'),

                    Forms\Components\Textarea::make('review')
                        ->label('Review / Notes')
                        ->rows(3)
                        ->placeholder('Add any notes about this container (e.g. duty paid, held at port)…'),
                ])
                ->action(function (array $data): void {
                    // Resolve 2-digit year from first shipment in this container
                    $year     = null;
                    $shipment = Shipment::where('container_number', $data['container_number'])
                        ->whereNotNull('shipping_reference')
                        ->first();

                    if ($shipment?->shipping_reference) {
                        $parsed = Shipment::parseShippingReference($shipment->shipping_reference);
                        $year   = $parsed['year'] ?? null;
                    }

                    ShipmentContainer::updateOrCreate(
                        ['container_number' => $data['container_number']],
                        [
                            'container_year' => $year,
                            'is_cleared'     => $data['is_cleared'] ?? false,
                            'review'         => $data['review'] ?? null,
                        ]
                    );

                    $label = $data['is_cleared'] ? 'marked as Cleared' : 'marked as Not Cleared';

                    Notification::make()
                        ->title('Container Updated')
                        ->body("CON{$data['container_number']} has been {$label}.")
                        ->success()
                        ->send();
                })
                ->modalSubmitActionLabel('Save Container Status'),
        ];
    }
}
