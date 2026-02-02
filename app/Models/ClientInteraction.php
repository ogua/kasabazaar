<?php

namespace App\Models;

use App\Enums\InteractionType;
use App\Enums\InteractionOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientInteraction extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'interaction_type' => InteractionType::class,
        'outcome' => InteractionOutcome::class,
        'follow_up_date' => 'datetime',
        'follow_up_completed' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function scopePendingFollowUp($query)
    {
        return $query->where('follow_up_completed', false)
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '<=', now());
    }

    public function scopeUpcomingFollowUp($query)
    {
        return $query->where('follow_up_completed', false)
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '>', now());
    }
}
