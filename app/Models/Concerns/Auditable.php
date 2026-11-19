<?php

namespace App\Models\Concerns;

use App\Models\Activity;
use App\Services\Audit\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use WeakMap;

/**
 * Records every create / update / delete / restore of the model in the audit trail
 * (App\Models\Activity), with only the attributes that changed, old → new.
 *
 * A model tunes what is recorded with optional static properties:
 *
 *  - `$auditLog`       module name shown in the log (shipments, investors, cashbook…)
 *  - `$auditExcept`    derived or noisy columns never recorded (running balances, mirrors)
 *  - `$auditMasked`    recorded with all but the last four characters hidden (account numbers)
 *  - `$auditRedacted`  recorded only as "[redacted]" — tokens, encrypted payout details, ID scans
 *  - `$auditHighRisk`  a change to any of these flags the entry as high risk (bank details, amounts…)
 *
 * and optional methods `auditLabel(): ?string` (how the record is named in the log) and
 * `auditScope(): ?array` ([parent class, parent id]) so a child — a payment, an item, an
 * investment transaction — also shows in its parent's history.
 *
 * A service that changes a record for a named reason calls `$model->auditAs('approved', ...)`
 * before saving: the automatic entry is then recorded under that event instead of a plain
 * "updated", so one action never leaves two rows.
 */
trait Auditable
{
    use LogsActivity;

    /** @var array{event: string, description: ?string, properties: array<string, mixed>, high_risk: bool}|null */
    protected ?array $pendingAuditEvent = null;

    /** Activities already tapped — spatie taps the subject twice per entry. */
    private static ?WeakMap $tappedActivities = null;

    /** @var list<string> */
    private static array $globalAuditExcept = ['password', 'remember_token', 'created_at', 'updated_at'];

    protected static function bootAuditable(): void
    {
        static::saved(function ($model): void {
            $model->flushPendingAuditEvent();
        });

        static::deleted(function ($model): void {
            $model->flushPendingAuditEvent();
        });
    }

    /**
     * Record the next save of this model as a named event ("approved", "status_changed"…).
     *
     * @param  array<string, mixed>  $properties
     */
    public function auditAs(string $event, ?string $description = null, array $properties = [], bool $highRisk = false): static
    {
        $this->pendingAuditEvent = [
            'event' => $event,
            'description' => $description,
            'properties' => $properties,
            'high_risk' => $highRisk,
        ];

        return $this;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(static::auditLogName())
            ->logExcept(static::auditExcludedAttributes())
            ->dontLogIfAttributesChangedOnly(static::auditExcludedAttributes())
            ->setDescriptionForEvent(fn (string $eventName): string => $this->auditDescription($eventName));
    }

    public function tapActivity(ActivityContract $activity, string $eventName): void
    {
        self::$tappedActivities ??= new WeakMap;

        if (isset(self::$tappedActivities[$activity])) {
            return;
        }

        self::$tappedActivities[$activity] = true;

        $activity->subject_label ??= $this->auditLabel();

        $scope = $this->auditScope();

        if (is_array($scope) && filled($scope[1] ?? null)) {
            $activity->scope_type ??= $scope[0];
            $activity->scope_id ??= (string) $scope[1];
        }

        $properties = collect($activity->properties ?? []);

        // Spatie compares the in-memory originals with a fresh read, so database defaults a
        // freshly created model never loaded look like changes. Keep what this save changed.
        if ($eventName === 'updated') {
            $changed = array_keys($this->getChanges());

            foreach (['attributes', 'old'] as $side) {
                if ($properties->has($side)) {
                    $properties->put($side, array_intersect_key((array) $properties->get($side), array_flip($changed)));
                }
            }
        }

        $changedKeys = array_keys((array) ($properties->get('attributes') ?? $properties->get('old') ?? []));

        foreach (['attributes', 'old'] as $side) {
            if ($properties->has($side)) {
                $properties->put($side, $this->protectAuditValues((array) $properties->get($side)));
            }
        }

        $isModelEvent = in_array($eventName, ['created', 'updated', 'deleted', 'restored'], true);
        $highRisk = ($eventName === 'updated' && array_intersect($changedKeys, static::auditHighRiskAttributes()) !== [])
            || ($eventName === 'deleted' && static::auditHighRiskAttributes() !== []);

        if ($isModelEvent && $this->pendingAuditEvent !== null) {
            $pending = $this->pendingAuditEvent;
            $this->pendingAuditEvent = null;

            $activity->event = $pending['event'];
            $activity->description = $pending['description'] ?? $this->auditDescription($pending['event']);
            $properties = $properties->merge($pending['properties']);
            $highRisk = $highRisk || $pending['high_risk'];
        }

        $activity->properties = $properties;

        if ($highRisk) {
            $activity->risk = Activity::RISK_HIGH;
        }
    }

    /**
     * The audit trail of this record and of its children (items, payments, transactions…).
     */
    public function auditTrail(): Builder
    {
        return Activity::query()->forRecord($this)->latest('id');
    }

    /**
     * How the record is named in the log, e.g. "KR-00123" or "Ama Mensah".
     */
    public function auditLabel(): ?string
    {
        foreach (['shipping_reference', 'reference', 'trip_reference', 'order_number', 'registration_number', 'payment_ref', 'pv_no', 'invoice_number', 'name', 'business_name', 'title', 'employee_id', 'email', 'code', 'key', 'slug'] as $attribute) {
            $value = $this->getAttribute($attribute);

            if (is_scalar($value) && filled($value)) {
                return (string) $value;
            }
        }

        return Str::limit((string) $this->getKey(), 8, '');
    }

    /**
     * [parent class, parent id] — so this record also appears in its parent's history.
     *
     * @return array{0: class-string, 1: mixed}|null
     */
    public function auditScope(): ?array
    {
        return null;
    }

    public static function auditLogName(): string
    {
        return isset(static::$auditLog) ? static::$auditLog : Str::snake(Str::pluralStudly(class_basename(static::class)));
    }

    /**
     * @return list<string>
     */
    public static function auditExcludedAttributes(): array
    {
        return array_values(array_unique(array_merge(
            self::$globalAuditExcept,
            isset(static::$auditExcept) ? static::$auditExcept : [],
        )));
    }

    /**
     * @return list<string>
     */
    public static function auditHighRiskAttributes(): array
    {
        return isset(static::$auditHighRisk) ? static::$auditHighRisk : [];
    }

    protected function auditDescription(string $eventName): string
    {
        $type = Str::headline(class_basename(static::class));
        $label = $this->auditLabel();
        $verb = match ($eventName) {
            'created', 'updated', 'deleted', 'restored' => $eventName,
            default => Str::lower(Str::headline($eventName)),
        };

        $description = trim("{$type} {$label}")." {$verb}";

        if ($eventName === 'updated') {
            $changed = array_values(array_diff(array_keys($this->getChanges()), static::auditExcludedAttributes()));

            if ($changed !== []) {
                $description .= ': '.Str::limit(implode(', ', array_map(fn (string $field) => Str::lower(Str::headline($field)), $changed)), 180);
            }
        }

        return $description;
    }

    /**
     * Hide secrets before they reach the trail: redacted columns and anything that looks like a
     * token become "[redacted]"; masked columns keep only their last four characters.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function protectAuditValues(array $values): array
    {
        $redacted = isset(static::$auditRedacted) ? static::$auditRedacted : [];
        $masked = isset(static::$auditMasked) ? static::$auditMasked : [];

        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($key, $redacted, true) || preg_match('/(^|_)token$|code_hash|secret/i', (string) $key)) {
                $values[$key] = '[redacted]';

                continue;
            }

            if (in_array($key, $masked, true) && is_scalar($value)) {
                $values[$key] = Audit::mask((string) $value);
            }
        }

        return $values;
    }

    /**
     * A named event that no attribute change carried (nothing logged changed, or the save was
     * a no-op) is still recorded, on its own.
     */
    protected function flushPendingAuditEvent(): void
    {
        if ($this->pendingAuditEvent === null) {
            return;
        }

        $pending = $this->pendingAuditEvent;
        $this->pendingAuditEvent = null;

        Audit::event(
            $this,
            $pending['event'],
            $pending['description'] ?? $this->auditDescription($pending['event']),
            Arr::wrap($pending['properties']),
            $pending['high_risk'],
        );
    }
}
