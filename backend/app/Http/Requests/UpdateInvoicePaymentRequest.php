<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['sometimes', 'required', 'integer', 'exists:payments,id'],
            'invoice_id' => ['sometimes', 'nullable', 'integer', 'exists:invoices,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
        ];
    }
}
