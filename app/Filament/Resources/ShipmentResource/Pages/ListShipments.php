<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\ShippingStatus;
use App\Filament\Resources\ShipmentResource;
use App\Jobs\BulkContainerShipmentStatusJob;
use App\Models\ClearingAgent;
use App\Models\ContainerClearance;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Services\ShipmentNotifier;
use Closure;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

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
                            $set('clearing_agent_id', $container?->clearing_agent_id);
                        }),

                    Forms\Components\Toggle::make('is_cleared')
                        ->label('Container Cleared at Customs')
                        ->onColor('success')
                        ->offColor('danger')
                        ->live()
                        ->helperText('Toggle ON when this container has been cleared at customs.'),

                    Forms\Components\Select::make('clearing_agent_id')
                        ->label('Clearing Agent')
                        ->options(fn () => ClearingAgent::query()->active()->pluck('name', 'id'))
                        ->searchable()
                        ->required(fn (Forms\Get $get) => (bool) $get('is_cleared'))
                        ->helperText('Which agent cleared (or is responsible for) this container.'),

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

                    $clearingAgentId = $data['clearing_agent_id'] ?? null;
                    $clearedAt = $nowCleared ? now() : null;

                    ShipmentContainer::updateOrCreate(
                        ['container_number' => $containerNumber],
                        [
                            'container_year' => $year,
                            'is_cleared' => $nowCleared,
                            'review' => $data['review'] ?? null,
                            'clearing_agent_id' => $clearingAgentId,
                            'cleared_at' => $clearedAt,
                        ]
                    );

                    if ($clearingAgentId) {
                        ContainerClearance::create([
                            'container_number' => $containerNumber,
                            'container_year' => $year,
                            'clearing_agent_id' => $clearingAgentId,
                            'is_cleared' => $nowCleared,
                            'review' => $data['review'] ?? null,
                            'recorded_by' => auth()->id(),
                            'cleared_at' => $clearedAt ?? now(),
                        ]);
                    }

                    $label = $nowCleared ? 'marked as Cleared' : 'marked as Not Cleared';
                    $containerRef = 'CON'.$containerNumber;

                    // Notify clients only when clearance state actually changes
                    if ($notifyClients && $previous !== $nowCleared) {
                        $shipments = Shipment::query()
                            ->where('container_number', '=', $containerNumber)
                            ->get();

                        $event = $nowCleared ? 'container_cleared' : 'container_update';

                        foreach ($shipments as $s) {
                            ShipmentNotifier::sent($s, $event, ['note' => $data['review'] ?? null]);
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

    /**
     * Quick tabs for the delivery stages, receipt confirmation and payment status.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All')
                ->badge(fn (): int => $this->countShipments(fn (Builder $query): Builder => $query)),
        ];

        $stageIcons = [
            ShippingStatus::pending->value => 'heroicon-m-clock',
            ShippingStatus::pickup->value => 'heroicon-m-hand-raised',
            ShippingStatus::shipped->value => 'heroicon-m-truck',
            ShippingStatus::cleared->value => 'heroicon-m-shield-check',
            ShippingStatus::delivered->value => 'heroicon-m-home',
            ShippingStatus::cancelled->value => 'heroicon-m-x-circle',
        ];

        foreach ($stageIcons as $value => $icon) {
            $status = ShippingStatus::from($value);
            $scope = fn (Builder $query): Builder => $query->where('status', $value);

            $tabs[$value] = $this->makeTab(ucfirst($value), $icon, $status->getColor(), $scope);
        }

        $tabs['received'] = $this->makeTab('Received', 'heroicon-m-check-badge', 'success',
            fn (Builder $query): Builder => $query->where('is_received', true));

        $tabs['awaiting_receipt'] = $this->makeTab('Delivered, Not Received', 'heroicon-m-exclamation-triangle', 'warning',
            fn (Builder $query): Builder => $query
                ->where('status', ShippingStatus::delivered->value)
                ->where('is_received', false));

        $tabs['unpaid'] = $this->makeTab('Unpaid', 'heroicon-m-banknotes', 'danger',
            fn (Builder $query): Builder => $query->where(
                fn (Builder $q): Builder => $q->where('payment_status', 'pending')->orWhereNull('payment_status')
            ));

        $tabs['partial'] = $this->makeTab('Part Paid', 'heroicon-m-banknotes', 'warning',
            fn (Builder $query): Builder => $query->where('payment_status', 'partial'));

        $tabs['paid'] = $this->makeTab('Paid', 'heroicon-m-banknotes', 'success',
            fn (Builder $query): Builder => $query->where('payment_status', 'paid'));

        return $tabs;
    }

    protected function makeTab(string $label, string $icon, string $color, Closure $scope): Tab
    {
        return Tab::make($label)
            ->icon($icon)
            ->badgeColor($color)
            ->modifyQueryUsing($scope)
            ->badge(fn (): int => $this->countShipments($scope));
    }

    /**
     * Count the current branch's shipments that match a tab's scope.
     */
    protected function countShipments(Closure $scope): int
    {
        return $scope(ShipmentResource::getEloquentQuery()->reorder())->count();
    }
}
