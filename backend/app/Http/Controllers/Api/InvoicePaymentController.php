<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoicePaymentRequest;
use App\Http\Requests\UpdateInvoicePaymentRequest;
use App\Http\Resources\InvoicePaymentResource;
use App\Models\InvoicePayment;

class InvoicePaymentController extends Controller
{
    public function index()
    {
        return InvoicePaymentResource::collection(
            InvoicePayment::query()->orderBy('id', 'desc')->paginate(25)
        );
    }

    public function store(StoreInvoicePaymentRequest $request)
    {
        $invoicePayment = InvoicePayment::create($request->validated());
        return new InvoicePaymentResource($invoicePayment);
    }

    public function show(InvoicePayment $invoicePayment)
    {
        return new InvoicePaymentResource($invoicePayment);
    }

    public function update(UpdateInvoicePaymentRequest $request, InvoicePayment $invoicePayment)
    {
        $invoicePayment->update($request->validated());
        return new InvoicePaymentResource($invoicePayment);
    }

    public function destroy(InvoicePayment $invoicePayment)
    {
        $invoicePayment->delete();
        return response()->noContent();
    }
}
