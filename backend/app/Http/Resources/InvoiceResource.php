<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance,
            'settled' => $this->settled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
