<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Available placeholders for message templates
     */
    public static array $placeholders = [
        '{{client_name}}' => 'Client full name',
        '{{tracking_number}}' => 'Shipment tracking number',
        '{{shipping_reference}}' => 'Shipping reference',
        '{{container_number}}' => 'Container number',
        '{{origin}}' => 'Origin location',
        '{{destination}}' => 'Destination location',
        '{{status}}' => 'Shipment status',
        '{{total}}' => 'Total amount',
        '{{balance}}' => 'Balance due',
        '{{estimated_delivery}}' => 'Estimated delivery date',
        '{{receiver_name}}' => 'Receiver name',
        '{{company_name}}' => 'Company name',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ShipmentMessage::class, 'message_template_id');
    }

    /**
     * Replace placeholders with actual values
     */
    public function renderBody(array $data): string
    {
        $body = $this->body;

        foreach ($data as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value ?? '', $body);
        }

        return $body;
    }

    /**
     * Replace placeholders in subject
     */
    public function renderSubject(array $data): string
    {
        $subject = $this->subject;

        foreach ($data as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value ?? '', $subject);
        }

        return $subject;
    }
}
