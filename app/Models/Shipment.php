<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\City;
use App\Models\Client;
use App\Models\Country;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PickupItems;
use App\Models\PickupSchedule;
use App\Models\Purchaseditem;
use App\Models\Receiver;
use App\Models\ShipmentItem;
use App\Models\ShipmentContainer;
use App\Models\ShipmentMedia;
use App\Models\ShipmentMessage;
use App\Models\ShipmentUpdate;
use App\Models\State;
use App\Models\Trip;
use App\Enums\ShippingStatus;
use App\Service\ExchangeRateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'status'                   => ShippingStatus::class,
        'insurance_accepted'       => 'boolean',
        'external_form_completed'  => 'boolean',
        'is_received'              => 'boolean',
        'signed_received_form'     => 'boolean',
        'is_disclaimer_agreed'     => 'boolean',
        'is_agreement_agreed'      => 'boolean',
        'exchange_rate_at_shipment' => 'decimal:4',
        'total'                    => 'decimal:2',
        'total_ghs'                => 'decimal:2',
    ];

    // Automatically update related items after saving
    protected static function booted()
    {
        // Before creating, capture exchange rate
        static::creating(function ($shipping) {
            if (!$shipping->exchange_rate_at_shipment) {
                try {
                    $exchangeService = app(ExchangeRateService::class);
                    $shipping->exchange_rate_at_shipment = $exchangeService->getCurrentRate('USD', 'GHS');
                } catch (\Exception $e) {
                    $shipping->exchange_rate_at_shipment = 12.0; // Fallback
                }
            }

            // Calculate GHS equivalent
            if ($shipping->total && $shipping->exchange_rate_at_shipment) {
                $shipping->total_ghs = $shipping->total * $shipping->exchange_rate_at_shipment;
            }
        });

        // Before saving, sync the DB fields from the shipping_reference
        static::saving(function ($shipping) {
            if ($shipping->shipping_reference) {
                $parsed = self::parseShippingReference($shipping->shipping_reference);
                if ($parsed) {
                    $shipping->container_number = $parsed['container_number'];
                    $shipping->client_sequence = $parsed['container_seq_year'];
                    $shipping->total_shipment_sequence = $parsed['client_seq'];
                }
            }

            // Recalculate GHS if total changes
            if ($shipping->isDirty('total') && $shipping->exchange_rate_at_shipment) {
                $shipping->total_ghs = $shipping->total * $shipping->exchange_rate_at_shipment;
            }
        });

        static::saved(function ($shipping) {
            $shipping->updateItemsShipmentId();
        });
    }

    /**
     * Parse shipping reference to extract components
     * Format: CON{N}-{YY}-C{CY}-{CS}
     *
     * @param string $reference The shipping reference string
     * @return array|null Array with container_number, year, container_seq_year, client_seq or null if invalid
     */
    public static function parseShippingReference(string $reference): ?array
    {
        // Pattern: CON49-25-C40-020 or CON49-25-C40-20
        if (preg_match('/^CON(\d+)-(\d{2})-C(\d+)-(\d+)$/', $reference, $matches)) {
            return [
                'container_number' => (int) $matches[1],
                'year' => $matches[2],
                'container_seq_year' => (int) $matches[3],
                'client_seq' => (int) $matches[4],
            ];
        }
        return null;
    }

    /**
     * Generate shipping reference in format: CON(CN)-(Y)-C(CY)-(CS)
     * CN = Container Number (global, increments for new shipments, stays same for existing)
     * Y = 2-digit Year (from shipment date)
     * CY = Container sequence for the year (starts at 1 each year, increments for new containers)
     * CS = Client sequence within that container (number of clients shipped in this container)
     *
     * Example: CON50-26-C1-1 means:
     * - Container number 50
     * - Year 2026
     * - First container of 2026
     * - First client in this container
     *
     * @param string $shipmentType 'new' or 'existing'
     * @param string|null $shipmentDate The shipment date to use for year calculation
     * @param string|null $excludeShipmentId Exclude this shipment from counts (for edits)
     * @param string|null $existingShipmentId The existing shipment to use for container reference
     */
    public static function generateShippingReference(
        string $shipmentType = 'new',
        ?string $shipmentDate = null,
        ?string $excludeShipmentId = null,
        ?string $existingShipmentId = null
    ): array {
        // Use provided shipment date or current date
        $date = $shipmentDate ? \Carbon\Carbon::parse($shipmentDate) : now();
        $currentYear = $date->format('Y');
        $yearShort = $date->format('y');

        // Get the latest shipping reference to parse actual values
        // This is more reliable than using DB fields which may have old/incorrect data
        $latestShipmentQuery = self::whereNotNull('shipping_reference')
            ->where('shipping_reference', 'like', 'CON%');

        if ($excludeShipmentId) {
            $latestShipmentQuery->where('id', '!=', $excludeShipmentId);
        }

        // Find the highest container number by parsing all references
        $allReferences = $latestShipmentQuery->pluck('shipping_reference')->toArray();

        $maxContainerNumber = 0;
        $maxContainerSeqThisYear = 0;

        foreach ($allReferences as $ref) {
            $parsed = self::parseShippingReference($ref);
            if ($parsed) {
                // Track highest global container number
                if ($parsed['container_number'] > $maxContainerNumber) {
                    $maxContainerNumber = $parsed['container_number'];
                }
                // Track highest container sequence for THIS year
                if ($parsed['year'] === $yearShort && $parsed['container_seq_year'] > $maxContainerSeqThisYear) {
                    $maxContainerSeqThisYear = $parsed['container_seq_year'];
                }
            }
        }

        if ($shipmentType === 'existing' && $existingShipmentId) {
            // Use the existing shipment's container details
            $existingShipment = self::find($existingShipmentId);

            if ($existingShipment && $existingShipment->shipping_reference) {
                $parsed = self::parseShippingReference($existingShipment->shipping_reference);

                if ($parsed) {
                    $containerNumber = $parsed['container_number'];
                    $containerSeqThisYear = $parsed['container_seq_year'];

                    // Count how many shipments exist with the same container number and year
                    $sameContainerRefs = array_filter($allReferences, function($ref) use ($containerNumber, $yearShort) {
                        $p = self::parseShippingReference($ref);
                        return $p && $p['container_number'] === $containerNumber && $p['year'] === $yearShort;
                    });

                    // Find the max client sequence in this container
                    $maxClientSeq = 0;
                    foreach ($sameContainerRefs as $ref) {
                        $p = self::parseShippingReference($ref);
                        if ($p && $p['client_seq'] > $maxClientSeq) {
                            $maxClientSeq = $p['client_seq'];
                        }
                    }

                    $shipmentSequence = $maxClientSeq + 1;
                } else {
                    // Fallback if parsing fails
                    $containerNumber = $maxContainerNumber > 0 ? $maxContainerNumber : 1;
                    $containerSeqThisYear = $maxContainerSeqThisYear > 0 ? $maxContainerSeqThisYear : 1;
                    $shipmentSequence = 1;
                }
            } else {
                // Fallback if existing shipment not found
                $containerNumber = $maxContainerNumber > 0 ? $maxContainerNumber : 1;
                $containerSeqThisYear = $maxContainerSeqThisYear > 0 ? $maxContainerSeqThisYear : 1;
                $shipmentSequence = 1;
            }
        } else {
            // New shipment = new container
            $containerNumber = $maxContainerNumber + 1;
            $containerSeqThisYear = $maxContainerSeqThisYear + 1;
            $shipmentSequence = 1; // First client in new container
        }

        // Format: CON(CN)-(Y)-C(CY)-(CS) e.g. CON50-26-C41-001
        $reference = sprintf(
            'CON%d-%s-C%d-%03d',
            $containerNumber,
            $yearShort,
            $containerSeqThisYear,
            $shipmentSequence
        );

        return [
            'reference' => $reference,
            'container_number' => $containerNumber,
            'client_sequence' => $containerSeqThisYear, // Container sequence for the year
            'total_shipment_sequence' => $shipmentSequence, // Client sequence in container
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

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
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

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, "shipment_id");
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class, "shipment_id");
    }

    public function media(): HasMany
    {
        return $this->hasMany(ShipmentMedia::class, 'shipment_id');
    }

    public function pickupSchedules(): HasMany
    {
        return $this->hasMany(PickupSchedule::class, 'shipment_id');
    }

    public function sourcePickup(): BelongsTo
    {
        return $this->belongsTo(PickupSchedule::class, 'pickup_schedule_id');
    }

    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'trip_shipments')
            ->withPivot(['delivery_status', 'delivery_notes', 'delivered_at', 'receiver_signature'])
            ->withTimestamps();
    }

    /**
     * Get total expenses in USD for this shipment
     */
    public function getTotalExpensesUsdAttribute(): float
    {
        return $this->expenses()->sum('amount_usd');
    }

    /**
     * Get total expenses in GHS for this shipment
     */
    public function getTotalExpensesGhsAttribute(): float
    {
        return $this->expenses()->sum('amount_ghs');
    }

    /**
     * Get net profit (revenue - expenses) in USD
     */
    public function getNetProfitUsdAttribute(): float
    {
        return $this->total - $this->total_expenses_usd;
    }

    /**
     * Scope for filtering by container number (e.g., CON51)
     */
    public function scopeByContainer($query, string $containerNumber)
    {
        return $query->where('shipping_reference', 'like', "{$containerNumber}-%");
    }

    /**
     * Scope for filtering by year
     */
    public function scopeByYear($query, int $year)
    {
        $yearSuffix = substr((string) $year, -2);
        return $query->where('shipping_reference', 'like', "%-{$yearSuffix}-%");
    }

    /**
     * Scope for filtering by container sequence (e.g., C2)
     */
    public function scopeByContainerSequence($query, int $year, int $sequence)
    {
        $yearSuffix = substr((string) $year, -2);
        $seqCode = "C{$sequence}";
        return $query->where('shipping_reference', 'like', "%-{$yearSuffix}-{$seqCode}-%");
    }

    /**
     * Get the container clearance record for this shipment's container.
     */
    public function containerStatus(): BelongsTo
    {
        return $this->belongsTo(ShipmentContainer::class, 'container_number', 'container_number');
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
