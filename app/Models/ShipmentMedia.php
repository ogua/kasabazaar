<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class ShipmentMedia extends Model
{
    use HasUuids;

    protected $table = 'shipment_media';

    protected $guarded = ['id'];

    public const TYPES = [
        'image' => 'Image',
        'video' => 'Video',
        'document' => 'Document',
    ];

    public const STAGES = [
        'pickup' => 'At Pickup',
        'in-transit' => 'In Transit',
        'delivery' => 'At Delivery',
        'post-delivery' => 'Post Delivery',
    ];

    public const VIDEO_EXTENSIONS = ['mp4', 'mov', 'qt', 'avi', 'webm', 'mkv', 'm4v', '3gp', 'mpeg', 'mpg'];

    public const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

    /**
     * MIME types the staff upload forms accept: photos, videos and paperwork.
     *
     * @var array<int, string>
     */
    public const ACCEPTED_UPLOAD_TYPES = [
        'image/*',
        'video/*',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
    ];

    protected static function booted(): void
    {
        static::deleted(function (ShipmentMedia $media): void {
            if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }
        });
    }

    public static function typeForPath(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match (true) {
            in_array($extension, self::VIDEO_EXTENSIONS, true) => 'video',
            in_array($extension, self::DOCUMENT_EXTENSIONS, true) => 'document',
            default => 'image',
        };
    }

    /**
     * Staff who can update shipments may remove any file; everyone else only their own uploads.
     */
    public function isRemovableBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('update_shipment') || $this->uploaded_by === $user->id;
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? Str::headline((string) $this->stage);
    }

    /**
     * Human-readable size of the stored file, or null when it is missing from disk.
     */
    public function fileSize(): ?string
    {
        $disk = Storage::disk('public');

        if (! $this->file_path || ! $disk->exists($this->file_path)) {
            return null;
        }

        return Number::fileSize($disk->size($this->file_path), precision: 1);
    }

    public function url(): string
    {
        return asset('storage/'.$this->file_path);
    }

    public function fileName(): string
    {
        return basename($this->file_path);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
