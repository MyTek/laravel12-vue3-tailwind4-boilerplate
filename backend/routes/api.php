<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceItemController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InvoicePaymentController;
use App\Http\Controllers\Api\BillingController;

Route::prefix('v1')->group(function () {
    Route::apiResource('people', PersonController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('invoice-items', InvoiceItemController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('invoice-payments', InvoicePaymentController::class);

    // Patient billing “view model” endpoints
    Route::get('billing/patients/{person}', [BillingController::class, 'show']);
    Route::post('billing/patients/{person}/apply-credit', [BillingController::class, 'applyCredit']);
});
