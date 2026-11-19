<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'website';

    public function blog(): HasMany
    {
        return $this->hasMany(BlogCategory::class, 'cat_id');
    }
}
