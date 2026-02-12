<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Person;

class PaymentController extends Controller
{
    public function index()
    {
        return PaymentResource::collection(
            Payment::query()->orderBy('id', 'desc')->paginate(25)
        );
    }

    public function store(StorePaymentRequest $request)
    {
        $data = $request->validated();

        if ($request->boolean('use_factory')) {
            $payment = Payment::factory()->create($data);
            event(new \App\Events\PaymentCreated($payment->fresh()));
            return new PaymentResource($payment);
        }

        $payment = Payment::create($data);

        event(new \App\Events\PaymentCreated($payment->fresh()));
        return new PaymentResource($payment);
    }

    public function show(Payment $payment)
    {
        return new PaymentResource($payment);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $payment->update($request->validated());
        event(new \App\Events\PaymentCreated($payment->fresh()));
        return new PaymentResource($payment);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->noContent();
    }
}
