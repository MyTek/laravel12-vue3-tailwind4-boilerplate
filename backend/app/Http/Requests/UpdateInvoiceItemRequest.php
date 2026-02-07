<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['sometimes', 'required', 'integer', 'exists:invoices,id'],
            'amount' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
