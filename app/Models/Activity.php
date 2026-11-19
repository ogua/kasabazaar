<?php

namespace App\Models;

use App\Services\Audit\AuditContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * One audit-trail entry. Append-only: the trail is evidence, so it can never be edited or
 * deleted from the application. Context (channel, branch, impersonator, request) is filled in
 * here so every writer — the Auditable trait, App\Services\Audit\Audit, the auth listener —
 * records it the same way.
 *
 * @property string|null $subject_label
 * @property string|null $scope_type
 * @property string|null $scope_id
 * @property string|null $impersonator_id
 * @property string|null $branch_id
 * @property string|null $channel
 * @property string|null $source
 * @property string $risk
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property string|null $http_method
 * @property string|null $route_name
 */
class Activity extends SpatieActivity
{
    public const RISK_NORMAL = 'normal';

    public const RISK_HIGH = 'high';

    /** Longest string value kept verbatim in the changes; longer ones are summarised. */
    public const MAX_VALUE_LENGTH = 2000;

    protected static function booted(): void
    {
        static::creating(function (Activity $activity): ?bool {
            // An "updated" entry left with nothing changed (only excluded or derived fields
            // moved) is noise.
            if ($activity->event === 'updated' && $activity->isEmptyChange()) {
                return false;
            }

            $activity->fillContext();
            $activity->truncateLongValues();

            return null;
        });

        static::updating(function (): void {
            throw new LogicException('Audit entries are append-only and cannot be changed.');
        });

        static::deleting(function (): void {
            throw new LogicException('Audit entries are append-only and cannot be deleted.');
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function scopeForRecord(Builder $query, Model $record): Builder
    {
        $type = $record->getMorphClass();
        $id = (string) $record->getKey();

        return $query->where(function (Builder $query) use ($type, $id): void {
            $query->where(fn (Builder $q) => $q->where('subject_type', $type)->where('subject_id', $id))
                ->orWhere(fn (Builder $q) => $q->where('scope_type', $type)->where('scope_id', $id));
        });
    }

    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->where('risk', self::RISK_HIGH);
    }

    /**
     * "Ama Mensah", "System · paystack", "Guest".
     */
    public function actorLabel(): string
    {
        $name = $this->causer?->getAttribute('name');

        if (filled($name)) {
            return $name;
        }

        if (in_array($this->channel, ['console', 'queue', 'webhook'], true)) {
            return 'System'.(filled($this->source) ? ' · '.$this->source : '');
        }

        return filled($this->ip_address) ? 'Guest' : 'System';
    }

    public function subjectTypeLabel(): ?string
    {
        return filled($this->subject_type) ? Str::headline(class_basename($this->subject_type)) : null;
    }

    /**
     * Field-by-field changes, for the diff table.
     *
     * @return array<int, array{field: string, old: mixed, new: mixed}>
     */
    public function diff(): array
    {
        $properties = $this->properties instanceof Collection ? $this->properties : collect($this->properties);
        $new = (array) ($properties->get('attributes') ?? []);
        $old = (array) ($properties->get('old') ?? []);

        $rows = [];

        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            $rows[] = [
                'field' => (string) $field,
                'old' => $old[$field] ?? null,
                'new' => $new[$field] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Properties other than the attribute changes (counts, reasons, references…).
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        $properties = $this->properties instanceof Collection ? $this->properties : collect($this->properties);

        return $properties->except(['attributes', 'old'])->all();
    }

    /**
     * A recorded value as staff should read it.
     */
    public static function displayValue(mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => '—',
            is_bool($value) => $value ? 'Yes' : 'No',
            is_array($value) => Str::limit((string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 300),
            default => Str::limit((string) $value, 300),
        };
    }

    public function eventColor(): string
    {
        return match (true) {
            $this->risk === self::RISK_HIGH => 'danger',
            in_array($this->event, ['deleted', 'login_failed', 'login_blocked', 'signup_code_failed', 'account_deleted', 'tokens_revoked'], true) => 'danger',
            in_array($this->event, ['created', 'login', 'restored', 'signup_completed'], true) => 'success',
            str_starts_with((string) $this->event, 'impersonation') => 'warning',
            $this->event === 'downloaded' => 'gray',
            default => 'info',
        };
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'admin' => 'Admin panel',
            'client' => 'Client portal',
            'investor' => 'Investor portal',
            'api' => 'Mobile app / API',
            'webhook' => 'Webhook',
            'queue' => 'Background job',
            'console' => 'Scheduled / console',
            'web' => 'Website',
            default => Str::headline((string) $this->channel),
        };
    }

    private function isEmptyChange(): bool
    {
        $properties = $this->properties instanceof Collection ? $this->properties : collect($this->properties ?? []);

        return empty($properties->get('attributes')) && empty($properties->get('old')) && $properties->except(['attributes', 'old'])->isEmpty();
    }

    private function fillContext(): void
    {
        $context = AuditContext::instance();

        if (blank($this->causer_id)) {
            $backgroundCauser = $context->backgroundCauserId();

            if ($backgroundCauser !== null) {
                $this->causer_type = (new User)->getMorphClass();
                $this->causer_id = $backgroundCauser;
            }
        }

        $this->channel ??= $context->channel();
        $this->source ??= $context->source();
        $this->impersonator_id ??= $context->impersonatorId();
        $this->risk ??= self::RISK_NORMAL;

        if (blank($this->branch_id)) {
            $subject = $this->relationLoaded('subject') ? $this->getRelation('subject') : null;
            $causer = $this->relationLoaded('causer') ? $this->getRelation('causer') : null;
            $scope = null;

            if (filled($this->scope_type) && filled($this->scope_id) && class_exists($this->scope_type)) {
                $scope = $this->scope_type::query()->find($this->scope_id);
            }

            if ($causer === null && filled($this->causer_id)) {
                $causer = User::query()->find($this->causer_id);
            }

            $this->branch_id = $context->branchIdFor($subject, $scope, $causer);
        }

        foreach ($context->requestData() as $column => $value) {
            $this->{$column} ??= $value;
        }

        if (filled($this->subject_label)) {
            $this->subject_label = Str::limit($this->subject_label, 250, '');
        }
    }

    private function truncateLongValues(): void
    {
        $properties = $this->properties instanceof Collection ? $this->properties : collect($this->properties ?? []);

        $this->properties = $properties->map(fn ($value) => $this->truncate($value));
    }

    private function truncate(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->truncate($item), $value);
        }

        if (is_string($value) && mb_strlen($value) > self::MAX_VALUE_LENGTH) {
            return '[changed, '.mb_strlen($value).' chars]';
        }

        return $value;
    }
}
