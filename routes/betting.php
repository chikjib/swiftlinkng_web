<?php
use App\Http\Controllers\API\BettingController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth:api', 'throttle:60,1'])->prefix('betting')->group(function () {
    Route::get('billers', [BettingController::class, 'billers']);
    Route::get('billers/{biller}/items', [BettingController::class, 'items']);
    Route::post('verify', [BettingController::class, 'verify']);
    Route::get('accounts', [BettingController::class, 'accounts']);
    Route::post('accounts', [BettingController::class, 'saveAccount']);
    Route::delete('accounts/{account}', [BettingController::class, 'deleteAccount']);
    Route::get('fundings', [BettingController::class, 'history']);
    Route::post('fundings', [BettingController::class, 'purchase'])->middleware('transaction.pin');
    Route::get('fundings/{reference}', [BettingController::class, 'status']);
});
