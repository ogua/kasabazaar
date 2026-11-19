<?php

namespace App\Services\Audit;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * One-line audit entries for things a model save does not capture: sign-ins, downloads,
 * emails sent, bulk/quiet writes and other named business events. Context (who, where,
 * branch, impersonator, request) is filled in by App\Models\Activity.
 *
 * Model changes themselves are recorded automatically by App\Models\Concerns\Auditable.
 */
class Audit
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function event(
        ?Model $subject,
        string $event,
        string $description,
        array $properties = [],
        bool $highRisk = false,
        ?string $log = null,
        ?Model $causer = null,
    ): ?Activity {
        $logName = $log ?? ($subject !== null && method_exists($subject, 'auditLogName') ? $subject::auditLogName() : 'general');

        return self::write($logName, $event, $description, $subject, $properties, $highRisk, $causer);
    }

    /**
     * Sign-ins, password changes, impersonation, permission changes.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function security(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        bool $highRisk = false,
        ?Model $causer = null,
    ): ?Activity {
        return self::write('auth', $event, $description, $subject, $properties, $highRisk, $causer);
    }

    /**
     * A PDF, Excel file or statement someone downloaded or generated.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function download(string $label, ?Model $subject = null, array $properties = []): ?Activity
    {
        return self::write('exports', 'downloaded', 'Downloaded '.$label, $subject, array_merge(['document' => $label], $properties));
    }

    /**
     * "••••4821": all but the last four characters hidden.
     */
    public static function mask(string $value): string
    {
        if (Str::startsWith($value, '••••')) {
            return $value;
        }

        return mb_strlen($value) <= 4 ? '••••' : '••••'.mb_substr($value, -4);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private static function write(
        string $logName,
        string $event,
        string $description,
        ?Model $subject,
        array $properties,
        bool $highRisk = false,
        ?Model $causer = null,
    ): ?Activity {
        try {
            $logger = activity($logName)
                ->event($event)
                ->withProperties($properties)
                ->tap(function (Activity $activity) use ($highRisk, $subject): void {
                    if ($highRisk) {
                        $activity->risk = Activity::RISK_HIGH;
                    }

                    if ($subject !== null && blank($activity->subject_label) && method_exists($subject, 'auditLabel')) {
                        $activity->subject_label = $subject->auditLabel();
                    }
                });

            if ($subject !== null) {
                $logger->performedOn($subject);
            }

            if ($causer !== null) {
                $logger->causedBy($causer);
            }

            /** @var Activity|null $activity */
            $activity = $logger->log(Str::limit($description, 1000));

            return $activity;
        } catch (Throwable $e) {
            // The audit trail must never take the business action down with it.
            report($e);

            return null;
        }
    }
}
