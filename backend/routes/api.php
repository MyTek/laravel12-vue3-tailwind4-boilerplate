<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'time' => now()->toISOString(),
    ]);
});

Route::get('/me', function (Request $request) {
    return response()->json([
        'authenticated' => false,
        'user' => null,
    ]);
});
