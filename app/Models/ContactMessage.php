<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContactMessage extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'read' => 'Read',
        'replied' => 'Replied',
    ];

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'read' => 'info',
            'replied' => 'success',
            default => 'gray',
        };
    }
}
