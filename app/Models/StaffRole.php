<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffRole extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
