<?php

namespace App\Service;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SystemSetting
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 600, fn () =>
            Setting::find($key)?->value ?? $default
        );
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
        Cache::forget("setting:{$key}");
    }

    public static function group(string $group): array
    {
        return Setting::where('group', $group)->pluck('value', 'key')->all();
    }
}
