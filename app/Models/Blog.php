<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'website';

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'cat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'postedby');
    }
}
