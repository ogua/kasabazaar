<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentMedia extends Model
{
    use HasUuids;

    protected $table = 'shipment_media';

    protected $guarded = ['id'];

    public const TYPES = [
        'image' => 'Image',
        'video' => 'Video',
    ];

    public const STAGES = [
        'pickup' => 'At Pickup',
        'in-transit' => 'In Transit',
        'delivery' => 'At Delivery',
        'post-delivery' => 'Post Delivery',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
