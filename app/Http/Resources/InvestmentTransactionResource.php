<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
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
