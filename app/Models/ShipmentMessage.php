<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentMessage extends Model
{
    use HasUuids;

    /**
     * Audiences a message can be sent to, keyed by the stored target_type.
     *
     * @var array<string, string>
     */
    public const TARGET_TYPES = [
        'client' => 'Specific Client',
        'shipment' => 'Specific Shipment',
        'container' => 'All in Container',
        'all' => 'All Clients',
        'investor' => 'Specific Investor',
        'all_investors' => 'All Active Investors',
        'staff' => 'Specific Staff Member',
        'all_staff' => 'All Active Staff (this branch)',
    ];

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

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get all recipients based on target type. Every recipient (Client,
     * Investor or Staff) exposes name, email and phone.
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
            'investor' => collect([$this->investor]),
            'all_investors' => Investor::query()->where('status', 'active')->get(),
            'staff' => collect([$this->staff]),
            'all_staff' => Staff::query()
                ->where('branch_id', $this->branch_id)
                ->where('employment_status', EmploymentStatus::Active)
                ->get(),
            default => collect(),
        };
    }

    /**
     * Get recipient emails
     */
    public function getRecipientEmails(): array
    {
        return $this->getRecipients()
            ->filter(fn ($client) => $client && $client->email)
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
            ->filter(fn ($client) => $client && $client->phone)
            ->pluck('phone')
            ->unique()
            ->values()
            ->toArray();
    }
}
