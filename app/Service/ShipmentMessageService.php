<?php

namespace App\Service;

use App\Models\Client;
use App\Models\Shipment;
use App\Models\MessageTemplate;
use App\Models\ShipmentMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class ShipmentMessageService
{
    /**
     * Send a message using a template
     */
    public static function sendFromTemplate(
        MessageTemplate $template,
        string $targetType,
        ?string $clientId = null,
        ?string $shipmentId = null,
        ?int $containerNumber = null
    ): ShipmentMessage {
        $message = ShipmentMessage::create([
            'branch_id' => Filament::getTenant()?->id,
            'message_template_id' => $template->id,
            'sent_by' => Auth::id(),
            'target_type' => $targetType,
            'client_id' => $clientId,
            'shipment_id' => $shipmentId,
            'container_number' => $containerNumber,
            'subject' => $template->subject,
            'body' => $template->body,
            'channel' => $template->type,
            'status' => 'pending',
        ]);

        self::processMessage($message);

        return $message;
    }

    /**
     * Send a custom message (without template)
     */
    public static function sendCustomMessage(
        string $subject,
        string $body,
        string $channel,
        string $targetType,
        ?string $clientId = null,
        ?string $shipmentId = null,
        ?int $containerNumber = null
    ): ShipmentMessage {
        $message = ShipmentMessage::create([
            'branch_id' => Filament::getTenant()?->id,
            'sent_by' => Auth::id(),
            'target_type' => $targetType,
            'client_id' => $clientId,
            'shipment_id' => $shipmentId,
            'container_number' => $containerNumber,
            'subject' => $subject,
            'body' => $body,
            'channel' => $channel,
            'status' => 'pending',
        ]);

        self::processMessage($message);

        return $message;
    }

    /**
     * Process and send the message
     */
    public static function processMessage(ShipmentMessage $message): void
    {
        try {
            $recipients = $message->getRecipients();

            if ($recipients->isEmpty()) {
                $message->update([
                    'status' => 'failed',
                    'error_message' => 'No recipients found',
                ]);
                return;
            }

            foreach ($recipients as $client) {
                if (!$client) continue;

                // Prepare data for placeholder replacement
                $data = self::prepareMessageData($client, $message);

                // Replace placeholders in subject and body
                $subject = self::replacePlaceholders($message->subject, $data);
                $body = self::replacePlaceholders($message->body, $data);

                // Send based on channel
                if (in_array($message->channel, ['email', 'both']) && $client->email) {
                    self::sendEmail($client->email, $subject, $body);
                }

                if (in_array($message->channel, ['sms', 'both']) && $client->phone) {
                    self::sendSms($client->phone, strip_tags($body));
                }
            }

            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Prepare data for placeholder replacement
     */
    protected static function prepareMessageData(Client $client, ShipmentMessage $message): array
    {
        $shipment = $message->shipment;

        $data = [
            'client_name' => $client->name ?? '',
            'company_name' => 'Rose Door To Door Shipping',
        ];

        if ($shipment) {
            $balance = $shipment->total - $shipment->payments->sum('amount');

            $data = array_merge($data, [
                'tracking_number' => $shipment->tracking_number ?? '',
                'shipping_reference' => $shipment->shipping_reference ?? '',
                'container_number' => $shipment->container_number ?? '',
                'origin' => $shipment->origin_branch_id ?? '',
                'destination' => $shipment->destination_branch_id ?? '',
                'status' => $shipment->status?->value ?? '',
                'total' => '$' . number_format($shipment->total ?? 0, 2),
                'balance' => '$' . number_format(max($balance, 0), 2),
                'estimated_delivery' => $shipment->estimated_delivery_date
                    ? \Carbon\Carbon::parse($shipment->estimated_delivery_date)->format('j M Y')
                    : 'TBD',
            ]);

            // Get first receiver name
            $receiver = $shipment->receivers->first();
            $data['receiver_name'] = $receiver?->receiver_name ?? '';
        }

        return $data;
    }

    /**
     * Replace placeholders in text
     */
    protected static function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }

    /**
     * Send email
     */
    protected static function sendEmail(string $to, string $subject, string $body): void
    {
        Mail::send([], [], function ($message) use ($to, $subject, $body) {
            $message->to($to)
                ->subject($subject)
                ->html($body);
        });
    }

    /**
     * Send SMS
     */
    protected static function sendSms(string $phone, string $message): void
    {
        // Use the existing NotificationService for SMS
        NotificationService::sendSmsToSender($phone, $message);
    }

    /**
     * Get shipments by container number
     */
    public static function getShipmentsByContainer(int $containerNumber): \Illuminate\Database\Eloquent\Collection
    {
        return Shipment::where('container_number', $containerNumber)
            ->with('client')
            ->get();
    }

    /**
     * Get all container numbers
     */
    public static function getContainerNumbers(): array
    {
        return Shipment::whereNotNull('container_number')
            ->distinct()
            ->orderByDesc('container_number')
            ->pluck('container_number')
            ->toArray();
    }
}
