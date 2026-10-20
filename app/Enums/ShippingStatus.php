<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The shipment stages, in the order management set in October 2026:
 * Pickup → Warehouse → Loaded → Shipped → In Transit → Harbour / Port GH → Sorting → Delivery → Received.
 *
 * `pending` (booked, not yet picked up) and `cancelled` sit outside that flow. The stored values are
 * read by the public tracking page on kasarose.com (`PublicShipmentTracking::statusMeta()`), so a
 * value added or renamed here must be mapped there too.
 */
enum ShippingStatus: string implements HasColor, HasLabel
{
    case pending = 'pending';
    case pickup = 'pickup';
    case warehouse = 'warehouse';
    case loaded = 'loaded';
    case shipped = 'shipped';
    case in_transit = 'in_transit';
    case port = 'port';
    case sorting = 'sorting';
    case delivered = 'delivered';
    case received = 'received';
    case cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::pending => 'Pending',
            self::pickup => 'Pickup',
            self::warehouse => 'Warehouse',
            self::loaded => 'Loaded',
            self::shipped => 'Shipped',
            self::in_transit => 'In Transit',
            self::port => 'Harbour / Port GH',
            self::sorting => 'Sorting',
            self::delivered => 'Delivery',
            self::received => 'Received',
            self::cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::pending, self::cancelled => 'danger',
            self::pickup, self::warehouse, self::loaded => 'warning',
            self::shipped, self::in_transit, self::port, self::sorting => 'info',
            self::delivered, self::received => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::pending => 'heroicon-m-clock',
            self::pickup => 'heroicon-m-hand-raised',
            self::warehouse => 'heroicon-m-building-storefront',
            self::loaded => 'heroicon-m-archive-box',
            self::shipped => 'heroicon-m-paper-airplane',
            self::in_transit => 'heroicon-m-globe-alt',
            self::port => 'heroicon-m-building-office-2',
            self::sorting => 'heroicon-m-queue-list',
            self::delivered => 'heroicon-m-truck',
            self::received => 'heroicon-m-check-badge',
            self::cancelled => 'heroicon-m-x-circle',
        };
    }

    /**
     * Whether the goods have reached Ghana (port onwards), for picking the status-history location.
     */
    public function isAtDestination(): bool
    {
        return in_array($this, [self::port, self::sorting, self::delivered, self::received], true);
    }

    /**
     * The stages where the goods have been handed over to the receiver.
     *
     * @return list<string>
     */
    public static function completedValues(): array
    {
        return [self::delivered->value, self::received->value];
    }

    /**
     * Booked but not yet shipped: still with us at origin.
     *
     * @return list<string>
     */
    public static function awaitingDispatchValues(): array
    {
        return [self::pending->value, self::pickup->value, self::warehouse->value, self::loaded->value];
    }

    /**
     * The stages between dispatch and handover — what the client portal calls "in transit".
     *
     * @return list<string>
     */
    public static function inTransitValues(): array
    {
        return [self::shipped->value, self::in_transit->value, self::port->value, self::sorting->value];
    }
}
