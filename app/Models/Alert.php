<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alert extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'severity' => AlertSeverity::class,
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('related', 'related_model', 'related_id');
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function dismiss(): void
    {
        $this->update([
            'is_dismissed' => true,
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeNotDismissed($query)
    {
        return $query->where('is_dismissed', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', AlertSeverity::Critical);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')
                ->orWhere('user_id', $userId);
        });
    }
}
