<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CashbookWithholdingTax extends Model
{
    use HasUuids;

    protected $table = 'cashbook_withholding_tax';

    protected $guarded = ['id'];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'rate'         => 'decimal:4',
        'wht_amount'   => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashbookWithholdingTax $wht) {
            $wht->wht_amount = round((float) $wht->gross_amount * (float) $wht->rate, 2);
        });
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public static function monthTotal(int $month, int $year): float
    {
        return (float) static::where('month', $month)->where('year', $year)->sum('wht_amount');
    }
}
