<?php

namespace Database\Factories;

use App\Enums\AppPlatform;
use App\Enums\MobileApp;
use App\Models\AppVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppVersion>
 */
class AppVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'app' => MobileApp::Client,
            'platform' => AppPlatform::Android,
            'version' => '1.0.0',
            'version_code' => 1,
            'minimum_supported_version' => null,
            'is_forced_update' => false,
            'is_active' => true,
            'release_notes' => null,
            'store_url' => null,
            'released_at' => now(),
        ];
    }

    public function forced(): static
    {
        return $this->state(['is_forced_update' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
