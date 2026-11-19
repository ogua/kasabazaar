<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class Setting extends Model
{
    use Auditable {
        tapActivity as auditableTapActivity;
    }

    protected static string $auditLog = 'settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'group'];

    /**
     * API keys, secrets, tokens, passwords and account SIDs (SMS, WhatsApp, payment providers).
     */
    public function holdsSecret(): bool
    {
        return (bool) preg_match('/key|secret|token|password|sid/i', (string) $this->getKey());
    }

    public function tapActivity(ActivityContract $activity, string $eventName): void
    {
        $this->auditableTapActivity($activity, $eventName);

        if ($this->holdsSecret()) {
            $activity->risk = Activity::RISK_HIGH;
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function protectAuditValues(array $values): array
    {
        if ($this->holdsSecret() && filled($values['value'] ?? null)) {
            $values['value'] = '[redacted]';
        }

        return $values;
    }
}
