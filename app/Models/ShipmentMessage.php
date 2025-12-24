<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentMessage extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get all recipients based on target type
     */
    public function getRecipients(): \Illuminate\Support\Collection
    {
        return match ($this->target_type) {
            'client' => collect([$this->client]),
            'shipment' => collect([$this->shipment?->client]),
            'container' => Shipment::where('container_number', $this->container_number)
                ->with('client')
                ->get()
                ->pluck('client')
                ->unique('id'),
            'all' => Client::all(),
            default => collect(),
        };
    }

    /**
     * Get recipient emails
     */
    public function getRecipientEmails(): array
    {
        return $this->getRecipients()
            ->filter(fn($client) => $client && $client->email)
            ->pluck('email')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get recipient phones
     */
    public function getRecipientPhones(): array
    {
        return $this->getRecipients()
            ->filter(fn($client) => $client && $client->phone)
            ->pluck('phone')
            ->unique()
            ->values()
            ->toArray();
    }
}
