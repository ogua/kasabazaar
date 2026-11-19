<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContainerClearance extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'shipments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_cleared' => 'boolean',
            'cleared_at' => 'datetime',
        ];
    }

    public function clearingAgent(): BelongsTo
    {
        return $this->belongsTo(ClearingAgent::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
