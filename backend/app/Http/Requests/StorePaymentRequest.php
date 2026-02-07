<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'payment_method' => ['nullable', 'string', 'size:4'],
            'payment_amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
