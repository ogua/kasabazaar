<?php

namespace App\Models;

use App\Models\Receiver;
use App\Enums\ShippingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Shipment extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => ShippingStatus::class,
        'insurance_accepted' => 'boolean',
        'external_form_completed' => 'boolean',
    ];

    // Automatically update related items after saving
    protected static function booted()
    {
        static::saved(function ($shipping) {
            $shipping->updateItemsShipmentId();
        });
    }

    /**
     * Generate shipping reference in format: CON(CN)-(Y)-(TC)-(TSY)
     * CN = Container Number
     * Y = Year
     * TC = Total Clients served this year
     * TSY = Total Shipments made (Year)
     */
    public static function generateShippingReference(string $shipmentType = 'new'): array
    {
        $currentYear = date('Y');
        $yearShort = date('y');

        // Get the latest container number
        $latestContainer = self::whereYear('created_at', $currentYear)
            ->whereNotNull('container_number')
            ->max('container_number') ?? 0;

        // Container number: increment for new shipment, keep same for existing
        $containerNumber = $shipmentType === 'new' ? $latestContainer + 1 : $latestContainer;
        if ($containerNumber === 0) $containerNumber = 1;

        // Total clients served this year (unique clients)
        $clientsThisYear = self::whereYear('created_at', $currentYear)
            ->distinct('client_id')
            ->count('client_id');
        $clientSequence = $clientsThisYear + 1;

        // Total shipments made this year
        $totalShipmentsThisYear = self::whereYear('created_at', $currentYear)->count();
        $shipmentSequence = $totalShipmentsThisYear + 1;

        // Format: CON(CN)-(Y)-(TC)-(TSY)
        $reference = sprintf(
            'CON%d-%s-%02d-%03d',
            $containerNumber,
            $yearShort,
            $clientSequence,
            $shipmentSequence
        );

        return [
            'reference' => $reference,
            'container_number' => $containerNumber,
            'client_sequence' => $clientSequence,
            'total_shipment_sequence' => $shipmentSequence,
        ];
    }

    /**
     * Generate unique external token for client self-service form
     */
    public static function generateExternalToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('external_token', $token)->exists());

        return $token;
    }

    /**
     * Get previous receivers for a client (for auto-populate)
     */
    public static function getPreviousReceivers(string $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return Receiver::whereHas('shipment', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })
            ->select('receiver_name', 'receiver_phone', 'receiver_email', 'country', 'state_region', 'city', 'address')
            ->distinct()
            ->get();
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

    public function messages(): HasMany
    {
        return $this->hasMany(ShipmentMessage::class, "shipment_id");
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
