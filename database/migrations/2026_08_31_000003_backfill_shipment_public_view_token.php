<?php

use App\Models\Shipment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Shipment::withoutGlobalScopes()
            ->whereNull('public_view_token')
            ->orWhere('public_view_token', '')
            ->chunkById(200, function ($shipments) {
                foreach ($shipments as $shipment) {
                    $shipment->forceFill(['public_view_token' => Str::random(32)])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        // no-op: tokens are harmless to keep
    }
};
