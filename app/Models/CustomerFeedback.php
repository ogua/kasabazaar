<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedback extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'customer_feedback';

    protected $guarded = ['id'];

    protected $casts = [
        'attachments' => 'array',
        'meta' => 'array',
        'rating' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public const FEEDBACK_SOURCES = [
        'Rose Shipment' => 'Rose Shipment',
        'NeoRide Africa' => 'NeoRide Africa',
    ];

    public const TYPES = [
        'feedback' => 'Feedback',
        'complaint' => 'Complaint',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const CATEGORIES = [
        'Delivery Speed' => 'Delivery Speed',
        'Item Condition' => 'Item Condition',
        'Late Delivery' => 'Late Delivery',
        'Wrong Address' => 'Wrong Address',
        'Damaged Item' => 'Damaged Item',
        'Damaged Packaging' => 'Damaged Packaging',
        'Wrong Item Sent' => 'Wrong Item Sent',
        'Missing Item' => 'Missing Item',
        'Driver Conduct' => 'Driver Conduct',
        'Ignored Instructions' => 'Ignored Instructions',
        'Tricycle Maintenance' => 'Tricycle Maintenance',
        'Driver Safety' => 'Driver Safety',
        'Route Efficiency' => 'Route Efficiency',
        'Traffic Handling' => 'Traffic Handling',
        'Other' => 'Other',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'reviewed' => 'Reviewed',
        'resolved' => 'Resolved',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shipment::class);
    }

    public static function generateComplaintToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('complaint_token', $token)->exists());

        return $token;
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'normal' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'gray',
        };
    }

    public function getRatingStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'reviewed' => 'info',
            'resolved' => 'success',
            default => 'gray',
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'Delivery Speed' => '⚡',
            'Item Condition' => '📦',
            'Late Delivery' => '⏰',
            'Wrong Address' => '📍',
            'Damaged Item' => '💔',
            'Damaged Packaging' => '📭',
            'Wrong Item Sent' => '🔄',
            'Missing Item' => '❓',
            'Driver Conduct' => '👤',
            'Ignored Instructions' => '📝',
            'Tricycle Maintenance' => '🔧',
            'Driver Safety' => '🛡️',
            'Route Efficiency' => '🗺️',
            'Traffic Handling' => '🚦',
            default => '📋',
        };
    }
}
