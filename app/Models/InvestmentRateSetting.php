<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentRateSetting extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'investors';

    /** @var list<string> */
    protected static array $auditHighRisk = ['annual_rate', 'year'];

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
