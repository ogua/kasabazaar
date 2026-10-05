<?php

namespace Database\Factories;

use App\Models\WhatsappMessage;
use App\Notifications\ShipmentAlert;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsappMessage>
 */
class WhatsappMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notification_type' => ShipmentAlert::class,
            'event' => 'status:shipped',
            'sender' => 'logistics',
            'phone' => '+233241234567',
            'template' => 'shipment_status_update',
            'reference' => 'SHP-'.fake()->numerify('####'),
            'gateway_message_id' => (string) Str::ulid(),
            'status' => WhatsappMessage::STATUS_QUEUED,
            'fallback_sms_body' => 'KASAROSE LOGISTICS: your shipment is now in transit.',
            'fallback_phone' => '0241234567',
        ];
    }
}
