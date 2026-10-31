<?php

namespace App\Models;

use App\Enums\AppPlatform;
use App\Enums\MobileApp;
use App\Services\AppUpdatePolicy;
use Database\Factories\AppVersionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A mobile app store build published from the admin panel. The newest active
 * row per app and platform — by version_code, not the version string — is what
 * the apps' update check compares against.
 */
class AppVersion extends Model
{
    /** @use HasFactory<AppVersionFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'app' => MobileApp::class,
            'platform' => AppPlatform::class,
            'version_code' => 'integer',
            'is_forced_update' => 'boolean',
            'is_active' => 'boolean',
            'released_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $forget = function (self $release): void {
            AppUpdatePolicy::forget($release->app, $release->platform);

            if ($release->wasChanged(['app', 'platform'])) {
                AppUpdatePolicy::forget($release->getOriginal('app'), $release->getOriginal('platform'));
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
