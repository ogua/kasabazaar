<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class InvestmentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            // Interest credits cover a period at a rate (Actual/365, compounded each Dec 31).
            'period_start' => $this->period_start ? Carbon::parse($this->period_start)->toDateString() : null,
            'period_end' => $this->period_end ? Carbon::parse($this->period_end)->toDateString() : null,
            'rate_applied' => $this->rate_applied !== null ? (float) $this->rate_applied : null,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'balance' => (float) $this->cl_balance,
            'year' => $this->year,
            'posted' => (bool) $this->posted,
            'description' => $this->description,
        ];
    }
}
