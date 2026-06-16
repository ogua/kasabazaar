<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\ShippingStatus;
use App\Filament\Resources\ShipmentResource;
use App\Jobs\BulkContainerShipmentStatusJob;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Service\NotificationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Mail;

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
                            $containers = Shipment::query()
                                ->whereNotNull('container_number')
                                ->select('container_number')
                                ->distinct()
                                ->orderByDesc('container_number')
                                ->pluck('container_number');

                            return $containers->mapWithKeys(function (int $cn): array {
                                $count = Shipment::query()->where('container_number', '=', $cn)->count();
                                $status = ShipmentContainer::query()
                                    ->where('container_number', '=', $cn)
                                    ->value('is_cleared');
                                $badge = $status ? ' — Cleared' : ' — Pending';

                                return [$cn => "CON{$cn}{$badge} ({$count} shipments)"];
                            })->toArray();
                        })
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?int $state, ?int $old, Forms\Set $set, Forms\Get $get): void {
                            if (! $state) {
                                return;
                            }
                            $container = ShipmentContainer::query()
                                ->where('container_number', '=', $state)
                                ->first();
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

                    Forms\Components\Toggle::make('notify_clients')
                        ->label('Notify Clients (Email & SMS)')
                        ->default(true)
                        ->helperText('Send email and SMS to all clients whose shipments are in this container.'),
                ])
                ->action(function (array $data): void {
                    $containerNumber = (int) $data['container_number'];
                    $nowCleared = (bool) ($data['is_cleared'] ?? false);
                    $notifyClients = (bool) ($data['notify_clients'] ?? true);

                    $year = null;
                    $shipment = Shipment::query()
                        ->where('container_number', '=', $containerNumber)
                        ->whereNotNull('shipping_reference')
                        ->first();

                    if ($shipment?->shipping_reference) {
                        $parsed = Shipment::parseShippingReference($shipment->shipping_reference);
                        $year = $parsed['year'] ?? null;
                    }

                    $previous = (bool) ShipmentContainer::query()
                        ->where('container_number', '=', $containerNumber)
                        ->value('is_cleared');

                    ShipmentContainer::updateOrCreate(
                        ['container_number' => $containerNumber],
                        [
                            'container_year' => $year,
                            'is_cleared' => $nowCleared,
                            'review' => $data['review'] ?? null,
                        ]
                    );

                    $label = $nowCleared ? 'marked as Cleared' : 'marked as Not Cleared';
                    $containerRef = 'CON'.$containerNumber;

                    // Notify clients only when clearance state actually changes
                    if ($notifyClients && $previous !== $nowCleared) {
                        $shipments = Shipment::query()
                            ->with('client')
                            ->where('container_number', '=', $containerNumber)
                            ->get();

                        $updatedAt = now()->format('j M Y, g:i A');
                        $statusKey = $nowCleared ? 'cleared' : 'pending';
                        $clearanceText = $nowCleared
                            ? 'Your container has been cleared at customs.'
                            : 'Your container clearance status has been updated.';

                        foreach ($shipments as $s) {
                            $client = $s->client;
                            if (! $client) {
                                continue;
                            }

                            if ($client->email) {
                                try {
                                    Mail::send('emails.shipment_status_updated', [
                                        'clientName' => $client->name,
                                        'shipmentRef' => $s->shipping_reference,
                                        'containerRef' => $containerRef,
                                        'status' => $statusKey,
                                        'note' => $data['review'] ?? null,
                                        'updatedAt' => $updatedAt,
                                        'receiverName' => $s->receivers()->value('receiver_name'),
                                    ], fn ($msg) => $msg
                                        ->to($client->email)
                                        ->subject("Container Update — {$s->shipping_reference}")
                                    );
                                } catch (\Throwable $e) {
                                    logger()->error('Container clearance email failed: '.$e->getMessage());
                                }
                            }

                            if ($client->phone) {
                                $sms = "Hi {$client->name}, {$clearanceText} Shipment: {$s->shipping_reference} ({$containerRef}).";
                                if (! empty($data['review'])) {
                                    $sms .= ' Note: '.$data['review'];
                                }
                                $sms .= ' — Rose Door to Door';
                                NotificationService::sendSmsToSender($client->phone, $sms);
                            }
                        }
                    }

                    Notification::make()
                        ->title('Container Updated')
                        ->body("{$containerRef} has been {$label}.")
                        ->success()
                        ->send();
                })
                ->modalSubmitActionLabel('Save Container Status'),

            Actions\Action::make('bulk_container_status')
                ->label('Bulk Status Update')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('info')
                ->modalHeading('Bulk Shipment Status Update by Container')
                ->modalDescription('Update the shipping status of ALL shipments in a container at once. Clients will be notified by email and SMS.')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Select::make('container_number')
                        ->label('Select Container')
                        ->options(function (): array {
                            $containers = Shipment::query()
                                ->whereNotNull('container_number')
                                ->select('container_number')
                                ->distinct()
                                ->orderByDesc('container_number')
                                ->pluck('container_number');

                            return $containers->mapWithKeys(function (int $cn): array {
                                $count = Shipment::query()->where('container_number', '=', $cn)->count();
                                $statuses = Shipment::query()
                                    ->where('container_number', '=', $cn)
                                    ->pluck('status')
                                    ->map(fn ($s) => ucfirst(is_string($s) ? $s : $s->value))
                                    ->unique()
                                    ->implode(' / ');

                                return [$cn => "CON{$cn} — {$count} shipments [{$statuses}]"];
                            })->toArray();
                        })
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?int $state, ?int $old, Forms\Set $set, Forms\Get $get): void {
                            if (! $state) {
                                return;
                            }
                            $set('shipment_count', Shipment::query()->where('container_number', '=', $state)->count());
                        }),

                    Forms\Components\Placeholder::make('shipment_count')
                        ->label('Shipments in Container')
                        ->content(fn (Forms\Get $get): string => $get('shipment_count')
                            ? $get('shipment_count').' shipment(s) will be updated'
                            : 'Select a container above'),

                    Forms\Components\Select::make('new_status')
                        ->label('New Status')
                        ->options(ShippingStatus::class)
                        ->required()
                        ->native(false),

                    Forms\Components\Textarea::make('note')
                        ->label('Message / Note to Clients')
                        ->rows(3)
                        ->placeholder('Optional message included in the email and SMS notification to clients…'),

                    Forms\Components\Toggle::make('notify_clients')
                        ->label('Notify Clients (Email & SMS)')
                        ->default(true)
                        ->helperText('Send email and SMS to all clients whose shipments are in this container.'),
                ])
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Update All & Notify')
                ->action(function (array $data): void {
                    $containerNumber = (int) $data['container_number'];
                    $notifyClients = (bool) ($data['notify_clients'] ?? true);
                    $count = Shipment::query()->where('container_number', '=', $containerNumber)->count();
                    $containerRef = 'CON'.$containerNumber;
                    $statusLabel = ucfirst($data['new_status']);

                    BulkContainerShipmentStatusJob::dispatch(
                        containerNumber: $containerNumber,
                        newStatus: $data['new_status'],
                        note: $data['note'] ?? null,
                        notifyClients: $notifyClients,
                    );

                    $notifyNote = $notifyClients ? ' Clients will be notified.' : '';

                    Notification::make()
                        ->title('Bulk Update Queued')
                        ->body("{$count} shipments in {$containerRef} are being updated to \"{$statusLabel}\".{$notifyNote}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
