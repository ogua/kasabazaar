<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    use HasUuids;

    public function category() : BelongsTo {
        return $this->belongsTo(BlogCategory::class, 'cat_id');
    }

    public function user() : BelongsTo {
        return $this->belongsTo(User::class, 'postedby');
    }
}
