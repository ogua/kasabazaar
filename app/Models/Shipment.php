<?php

namespace App\Models;

use App\Models\Receiver;
use App\Enums\ShippingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasUuids;


    protected $casts = [
        'status' => ShippingStatus::class
    ];

    // Automatically update related items after saving
    protected static function booted()
    {
        static::saved(function ($shipping) {
            $shipping->updateItemsShipmentId();
        });
    }
    

    public function items()
    {
        return $this->hasMany(ShipmentItem::class,"shipment_id");
    }

    public function puchaseditems()
    {
        return $this->hasMany(Purchaseditem::class,"shipment_id");
    }

    public function pickupitems()
    {
        return $this->hasMany(PickupItems::class,"shipment_id");
    }

    public function statusupdate()
    {
        return $this->hasMany(ShipmentUpdate::class,"shipment_id");
    }

    public function payments()
    {
        return $this->hasMany(Payment::class,"shipment_id");
    }

    public function receivers()
    {
        return $this->hasMany(Receiver::class,"shipment_id");
    }


    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function invoice() : HasOne {
        return $this->hasOne(Invoice::class,"shipment_id");
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class,"client_id");
    }

    public function mcity(): BelongsTo
    {
        return $this->belongsTo(City::class,"city");
    }


    public function mstate(): BelongsTo
    {
        return $this->belongsTo(State::class,"state_region");
    }


    public function mcountry(): BelongsTo
    {
        return $this->belongsTo(Country::class,"country");
    }


    // Custom method to update shipment_id for related items
    public function updateItemsShipmentId()
    {
        foreach ($this->receivers as $receiver) {
            foreach ($receiver->items as $item) {
                $item->update(['shipment_id' => $this->id]);
            }
        }
    }


}
