<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShipmentRequestLink extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'max_submissions' => 'integer',
        'submissions_count' => 'integer',
        'expires_at' => 'datetime',
        'last_submitted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Generate a URL-safe token that no other link uses.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(40);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shipmentRequests(): HasMany
    {
        return $this->hasMany(ShipmentRequest::class);
    }

    public function url(): string
    {
        return route('public-pickup-request', $this->token);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsedUp(): bool
    {
        return $this->max_submissions !== null && $this->submissions_count >= $this->max_submissions;
    }

    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired() && ! $this->isUsedUp();
    }

    /**
     * @return 'active'|'revoked'|'expired'|'used'
     */
    public function state(): string
    {
        return match (true) {
            $this->isRevoked() => 'revoked',
            $this->isUsedUp() => 'used',
            $this->isExpired() => 'expired',
            default => 'active',
        };
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn (Builder $q) => $q->whereNull('max_submissions')->orWhereColumn('submissions_count', '<', 'max_submissions'));
    }
}
