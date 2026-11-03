<?php

namespace App\Models;

use App\Service\NotificationService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A WhatsApp notification handed to the provider (Infobip or the Ogua
 * gateway), tracked until its callback reports delivered/read or failed.
 */
class WhatsappMessage extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    /**
     * Statuses only move forward; a late "sent" never overwrites "delivered".
     */
    public const STATUS_RANK = [
        self::STATUS_PENDING => 0,
        self::STATUS_QUEUED => 1,
        self::STATUS_SENT => 2,
        self::STATUS_DELIVERED => 3,
        self::STATUS_READ => 4,
        self::STATUS_FAILED => 5,
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fallback_sent_at' => 'datetime',
        ];
    }

    /**
     * Apply a provider's status report for the message with this gateway id; a
     * failure triggers the SMS fallback.
     */
    public static function applyStatusReport(?string $gatewayMessageId, string $status, ?string $errorCode = null, ?string $errorMessage = null): void
    {
        $message = filled($gatewayMessageId) ? static::where('gateway_message_id', $gatewayMessageId)->first() : null;

        if (! $message || ! isset(self::STATUS_RANK[$status])) {
            return;
        }

        if (self::STATUS_RANK[$status] <= (self::STATUS_RANK[$message->status] ?? -1)) {
            return;
        }

        if ($status === self::STATUS_FAILED) {
            $message->markFailed($errorCode, $errorMessage);
            $message->sendFallbackSms();

            return;
        }

        $message->update(['status' => $status]);
    }

    public function markFailed(?string $code, ?string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_code' => $code,
            'error_message' => $message,
        ]);
    }

    /**
     * Send the SMS version of this notification instead — at most once, however
     * many failure signals (a gateway error, then a failed callback) arrive.
     */
    public function sendFallbackSms(): void
    {
        if ($this->fallback_sent_at || blank($this->fallback_sms_body) || blank($this->fallback_phone)) {
            return;
        }

        $claimed = static::whereKey($this->getKey())
            ->whereNull('fallback_sent_at')
            ->update(['fallback_sent_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $this->refresh();

        NotificationService::sendSmsToSender($this->fallback_phone, $this->fallback_sms_body);
    }
}
