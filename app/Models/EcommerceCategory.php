<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceCategory extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'marketplace';

    protected $table = 'ecommerce_categories';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(EcommerceCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EcommerceCategory::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(EcommerceProduct::class, 'category_id');
    }
}
