<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'parameters' => 'array',
        'data' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
