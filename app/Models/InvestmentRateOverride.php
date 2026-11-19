<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentRateOverride extends Model
{
    use Auditable, HasUuids;

    protected static string $auditLog = 'investors';

    /** @var list<string> */
    protected static array $auditHighRisk = ['annual_rate', 'year'];

    protected $guarded = ['id'];

    protected $casts = [
        'annual_rate' => 'decimal:2',
    ];

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array{0: class-string, 1: mixed}|null
     */
    public function auditScope(): ?array
    {
        return [Investment::class, $this->investment_id];
    }
}
