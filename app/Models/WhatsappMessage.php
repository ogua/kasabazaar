<?php

namespace App\Models;

use App\Service\NotificationService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A WhatsApp notification handed to the Ogua gateway, tracked until the
 * gateway's callback reports delivered/read or failed.
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

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fallback_sent_at' => 'datetime',
        ];
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
