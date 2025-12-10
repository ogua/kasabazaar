<?php

namespace App\Models;

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
    ];

    public const FEEDBACK_SOURCES = [
        'Rose Shipment' => 'Rose Shipment',
        'NeoRide Africa' => 'NeoRide Africa',
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
