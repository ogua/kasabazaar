<?php

namespace App\Models;

use App\Models\Concerns\HasWhatsappConsent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receiver extends Model
{
    use HasUuids, HasWhatsappConsent;

    protected $guarded = ['id'];

    /**
     * A receiver is a third party entered by the shipping client, so unless
     * they opted in themselves, their WhatsApp consent is the client's attestation.
     */
    protected static function booted(): void
    {
        static::saving(function (self $receiver): void {
            if ($receiver->whatsapp_opt_in_at && blank($receiver->whatsapp_opt_in_source)) {
                $receiver->whatsapp_opt_in_source = 'client_attested';
            }
        });
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function mcity(): BelongsTo
    {
        return $this->belongsTo(City::class, "city");
    }

    public function mstate(): BelongsTo
    {
        return $this->belongsTo(State::class, "state_region");
    }

    public function mcountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, "country");
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class, "receiver_id");
    }
}
