<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentWithdrawalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investment_id' => $this->investment_id,
            'investment_reference' => $this->investment?->reference,
            'is_full_withdrawal' => (bool) $this->is_full_withdrawal,
            'requested_amount' => $this->requested_amount !== null ? (float) $this->requested_amount : null,
            'amount_paid' => (float) $this->amount_paid,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'notice_date' => $this->notice_date?->toDateString(),
            'required_action_by' => $this->required_action_by?->toDateString(),
            'payment_due_by' => $this->payment_due_by?->toDateString(),
            'rejection_reason' => $this->rejection_reason,
            'deferral_reason' => $this->deferral_reason,
            'created_at' => $this->created_at,
        ];
    }
}
