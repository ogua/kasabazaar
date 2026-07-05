<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentRateSetting extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'annual_rate' => 'decimal:2',
    ];

    public static function forYear(int $year): ?self
    {
        return self::where('year', $year)->first();
    }

    public function setByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
