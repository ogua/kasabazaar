<?php

namespace App\Models;

use App\Enums\InvestmentWebhookEventStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentWebhookEvent extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'status' => InvestmentWebhookEventStatus::class,
    ];

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function withdrawalRequest(): BelongsTo
    {
        return $this->belongsTo(InvestmentWithdrawalRequest::class, 'investment_withdrawal_request_id');
    }
}
