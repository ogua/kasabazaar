<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    use HasUuids;

    public function blog() : HasMany
    {
        return $this->hasMany(BlogCategory::class, 'cat_id');
    }
}
