<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'principal_amount' => (float) $this->principal_amount,
            'principal_amount_ghs' => (float) $this->principal_amount_ghs,
            'current_balance' => (float) $this->current_balance,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'deposit_gateway' => $this->deposit_gateway,
            'start_date' => $this->start_date?->toDateString(),
            'last_interest_posted_year' => $this->last_interest_posted_year,
            'created_at' => $this->created_at,
        ];
    }
}
