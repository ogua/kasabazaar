<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use Auditable, HasUuids, SoftDeletes;

    protected static string $auditLog = 'feedback';

    protected $guarded = ['id'];

    protected $casts = [
        'replied_at' => 'datetime',
        'sms_opt_in' => 'boolean',
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
