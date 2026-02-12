<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyCreditRequest;
use App\Models\Person;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\InvoicePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function show(Person $person): JsonResponse
    {
        // Invoices for this person
        $invoices = Invoice::query()
            ->where('person_id', $person->id)
            ->orderByDesc('id')
            ->get(['id', 'person_id', 'created_at']);

        $invoiceIds = $invoices->pluck('id')->all();

        // Total charges by invoice: sum(invoice_items.amount)
        $totalsByInvoice = InvoiceItem::query()
            ->select('invoice_id', DB::raw('COALESCE(SUM(amount), 0) as total_amount'))
            ->whereIn('invoice_id', $invoiceIds)
            ->groupBy('invoice_id')
            ->pluck('total_amount', 'invoice_id');

        // Paid amount by invoice: sum(invoice_payments.amount) where invoice_id = invoice
        $paidByInvoice = InvoicePayment::query()
            ->select('invoice_id', DB::raw('COALESCE(SUM(amount), 0) as paid_amount'))
            ->whereIn('invoice_id', $invoiceIds)
            ->whereNotNull('invoice_id')
            ->groupBy('invoice_id')
            ->pluck('paid_amount', 'invoice_id');

        // Payments (non-deleted) for this person
        $payments = Payment::query()
            ->where('person_id', $person->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get(['id', 'person_id', 'payment_method', 'payment_amount', 'deleted_at', 'created_at']);

        $paymentIds = $payments->pluck('id')->all();

        // InvoicePayments linked to this person’s payments (covers “applications of credit”)
        $invoicePayments = [];
        if (!empty($paymentIds)) {
            $invoicePayments = InvoicePayment::query()
                ->whereIn('payment_id', $paymentIds)
                ->orderByDesc('id')
                ->get(['id', 'payment_id', 'invoice_id', 'amount', 'created_at'])
                ->all();
        }

        // DeletedRaw: deleted payments + their invoice payments
        $deletedPayments = Payment::query()
            ->where('person_id', $person->id)
            ->whereNotNull('deleted_at')
            ->orderByDesc('deleted_at')
            ->get(['id', 'person_id', 'payment_method', 'payment_amount', 'deleted_at', 'created_at']);

        $deletedPaymentIds = $deletedPayments->pluck('id')->all();

        $deletedInvoicePayments = [];
        if (!empty($deletedPaymentIds)) {
            $deletedInvoicePayments = InvoicePayment::query()
                ->whereIn('payment_id', $deletedPaymentIds)
                ->orderByDesc('id')
                ->get(['id', 'payment_id', 'invoice_id', 'amount', 'created_at'])
                ->all();
        }

        // Shape invoices the way the frontend expects
        $invoicesOut = $invoices->map(function (Invoice $inv) use ($totalsByInvoice, $paidByInvoice) {
            $total = (float) ($totalsByInvoice[$inv->id] ?? 0);
            $paid  = (float) ($paidByInvoice[$inv->id] ?? 0);
            $balance = $total - $paid;

            return [
                'id' => $inv->id,
                'person_id' => $inv->person_id,
                'total_amount' => number_format($total, 2, '.', ''),
                'paid_amount' => number_format($paid, 2, '.', ''),
                'balance' => number_format($balance, 2, '.', ''),
                'settled' => abs($balance) < 0.00001,
                'created_at' => optional($inv->created_at)->toISOString(),
            ];
        })->values();

        $paymentsOut = $payments->map(function (Payment $p) {
            return [
                'id' => $p->id,
                'person_id' => $p->person_id,
                'payment_method' => $p->payment_method,
                'payment_amount' => number_format((float) $p->payment_amount, 2, '.', ''),
                'deleted_at' => $p->deleted_at ? $p->deleted_at->toISOString() : null,
                'created_at' => optional($p->created_at)->toISOString(),
            ];
        })->values();

        $invoicePaymentsOut = collect($invoicePayments)->map(function ($ip) {
            return [
                'id' => $ip->id,
                'payment_id' => $ip->payment_id,
                'invoice_id' => $ip->invoice_id,
                'amount' => number_format((float) $ip->amount, 2, '.', ''),
                'created_at' => optional($ip->created_at)->toISOString(),
            ];
        })->values();

        $deletedPaymentsOut = $deletedPayments->map(function (Payment $p) {
            return [
                'id' => $p->id,
                'person_id' => $p->person_id,
                'payment_method' => $p->payment_method,
                'payment_amount' => number_format((float) $p->payment_amount, 2, '.', ''),
                'deleted_at' => $p->deleted_at ? $p->deleted_at->toISOString() : null,
                'created_at' => optional($p->created_at)->toISOString(),
            ];
        })->values();

        $deletedInvoicePaymentsOut = collect($deletedInvoicePayments)->map(function ($ip) {
            return [
                'id' => $ip->id,
                'payment_id' => $ip->payment_id,
                'invoice_id' => $ip->invoice_id,
                'amount' => number_format((float) $ip->amount, 2, '.', ''),
                'created_at' => optional($ip->created_at)->toISOString(),
            ];
        })->values();

        return response()->json([
            'person' => [
                'id' => $person->id,
                'first_name' => $person->first_name ?? null,
                'last_name' => $person->last_name ?? null,
            ],
            'invoices' => $invoicesOut,
            'payments' => $paymentsOut,
            'invoice_payments' => $invoicePaymentsOut,
            'deleted_raw' => [
                'payments' => $deletedPaymentsOut,
                'invoice_payments' => $deletedInvoicePaymentsOut,
            ],
        ]);
    }

    public function applyCredit(Person $person, ApplyCreditRequest $request): JsonResponse
    {
        $paymentId = (int) $request->input('payment_id');
        $invoiceId = (int) $request->input('invoice_id');
        $amount    = (float) $request->input('amount');

        return DB::transaction(function () use ($person, $paymentId, $invoiceId, $amount) {
            // Lock payment row to prevent race conditions applying credit twice
            $payment = Payment::query()
                ->where('id', $paymentId)
                ->where('person_id', $person->id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found for this patient.'], 404);
            }

            $invoice = Invoice::query()
                ->where('id', $invoiceId)
                ->where('person_id', $person->id)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                return response()->json(['message' => 'Invoice not found for this patient.'], 404);
            }

            // PaymentAmount is always positive
            if ((float) $payment->payment_amount <= 0) {
                return response()->json(['message' => 'Payment amount must be positive.'], 422);
            }

            // Calculate available credits on this payment
            $alreadyApplied = (float) InvoicePayment::query()
                ->where('payment_id', $payment->id)
                ->sum('amount');

            $available = (float) $payment->payment_amount - $alreadyApplied;

            if ($amount <= 0) {
                return response()->json(['message' => 'Amount must be greater than 0.'], 422);
            }

            if ($amount - $available > 0.00001) {
                return response()->json([
                    'message' => 'Amount exceeds available credits for this payment.',
                    'meta' => [
                        'available' => number_format($available, 2, '.', ''),
                        'payment_amount' => number_format((float) $payment->payment_amount, 2, '.', ''),
                        'already_applied' => number_format($alreadyApplied, 2, '.', ''),
                    ],
                ], 422);
            }

            $ip = InvoicePayment::query()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);

//            event(new \App\Events\InvoiceUpdated($invoice->fresh()));

            return response()->json([
                'message' => 'Credit applied.',
                'invoice_payment' => [
                    'id' => $ip->id,
                    'payment_id' => $ip->payment_id,
                    'invoice_id' => $ip->invoice_id,
                    'amount' => number_format((float) $ip->amount, 2, '.', ''),
                    'created_at' => optional($ip->created_at)->toISOString(),
                ],
            ], 201);
        });
    }
}
