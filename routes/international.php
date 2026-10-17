<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\InternationalController as C;
Route::middleware(['auth:api', 'throttle:60,1'])->prefix('international')->group(function () {
    Route::get('countries', [C::class, 'countries']);
    Route::get('operators', [C::class, 'operators']);
    Route::post('preview', [C::class, 'preview']);
    Route::post('quotes', [C::class, 'quote']);
    Route::post('purchases', [C::class, 'purchase'])->middleware('transaction.pin');
    Route::get('purchases', [C::class, 'history']);
    Route::get('purchases/{reference}', [C::class, 'status']);
});
Route::middleware(['auth:api', 'admin.only', 'throttle:30,1'])->prefix('admin/international')->group(function () {
    Route::get('settings', [C::class, 'settings']);
    Route::put('settings', [C::class, 'updateSettings']);
    Route::post('sochitel/test', [C::class, 'testSochitel'])->middleware('throttle:5,1');
    Route::get('transactions', [C::class, 'transactions']);
    Route::post('transactions/{id}/resolve', [C::class, 'resolve']);
});
