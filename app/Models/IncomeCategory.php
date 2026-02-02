<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomeCategory extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
