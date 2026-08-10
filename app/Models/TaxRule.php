<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
