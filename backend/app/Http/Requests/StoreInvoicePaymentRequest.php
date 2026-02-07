<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
