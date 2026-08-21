<?php

namespace App\Models;

use App\Enums\TaxFilingType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A filed statutory return held on file. The system stores and serves these; it does
 * not compute Ghanaian tax — the figures come from the accountant.
 */
class TaxFiling extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'filing_type' => TaxFilingType::class,
        'filed_at' => 'date',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
