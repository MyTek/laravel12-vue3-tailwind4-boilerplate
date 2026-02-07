<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceItemRequest;
use App\Http\Requests\UpdateInvoiceItemRequest;
use App\Http\Resources\InvoiceItemResource;
use App\Models\InvoiceItem;

class InvoiceItemController extends Controller
{
    public function index()
    {
        return InvoiceItemResource::collection(
            InvoiceItem::query()->orderBy('id', 'desc')->paginate(25)
        );
    }

    public function store(StoreInvoiceItemRequest $request)
    {
        $item = InvoiceItem::create($request->validated());
        return new InvoiceItemResource($item);
    }

    public function show(InvoiceItem $invoiceItem)
    {
        return new InvoiceItemResource($invoiceItem);
    }

    public function update(UpdateInvoiceItemRequest $request, InvoiceItem $invoiceItem)
    {
        $invoiceItem->update($request->validated());
        return new InvoiceItemResource($invoiceItem);
    }

    public function destroy(InvoiceItem $invoiceItem)
    {
        $invoiceItem->delete();
        return response()->noContent();
    }
}
