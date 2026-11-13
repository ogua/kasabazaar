<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EcommerceOrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Packed = 'packed';
    case SentToWarehouse = 'sent_to_warehouse';
    case AtWarehouse = 'at_warehouse';
    case Inspected = 'inspected';
    case InspectionFailed = 'inspection_failed';
    case ReadyForPickup = 'ready_for_pickup';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AwaitingPayment => 'Awaiting Payment',
            self::Paid => 'Paid',
            self::Processing => 'Processing',
            self::Packed => 'Packed',
            self::SentToWarehouse => 'Sent to Warehouse',
            self::AtWarehouse => 'Received at Warehouse',
            self::Inspected => 'Inspected',
            self::InspectionFailed => 'Failed Inspection',
            self::ReadyForPickup => 'Ready for Pickup',
            self::Dispatched => 'Dispatched',
            self::InTransit => 'In Transit',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::AwaitingPayment => 'warning',
            self::Paid => 'info',
            self::Processing => 'info',
            self::Packed => 'info',
            self::SentToWarehouse => 'info',
            self::AtWarehouse => 'primary',
            self::Inspected => 'success',
            self::InspectionFailed => 'danger',
            self::ReadyForPickup => 'success',
            self::Dispatched => 'primary',
            self::InTransit => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Refunded => 'danger',
        };
    }
}
