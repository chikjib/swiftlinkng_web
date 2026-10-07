<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\EsimController;
use App\Http\Controllers\API\ServiceAdminController;
Route::middleware(['auth:api','throttle:60,1'])->group(function () {
    Route::get('services/status',[ServiceAdminController::class,'status']);
    Route::get('esim/terms',[EsimController::class,'terms']);
    Route::get('esim/locations',[EsimController::class,'locations']);
    Route::get('esim/packages',[EsimController::class,'packages']);
    Route::post('esim/quotes',[EsimController::class,'quote']);
    Route::post('esim/purchases',[EsimController::class,'purchase'])->middleware('transaction.pin');
    Route::get('esim/purchases',[EsimController::class,'history']);
    Route::get('esim/purchases/{reference}',[EsimController::class,'status']);
    Route::get('esim/profiles',[EsimController::class,'profiles']);
    Route::get('esim/profiles/{id}',[EsimController::class,'details']);
});
Route::middleware(['auth:api','admin.only','throttle:30,1'])->prefix('admin/services')->group(function () {
    Route::get('settings',[ServiceAdminController::class,'show']);
    Route::put('settings',[ServiceAdminController::class,'update']);
    Route::get('billers',[ServiceAdminController::class,'billers']);
    Route::get('transactions',[ServiceAdminController::class,'transactions']);
    Route::post('{service}/{id}/recheck',[ServiceAdminController::class,'recheck']);
    Route::post('esim/{id}/resolve',[ServiceAdminController::class,'resolveTopup']);
    Route::get('audits',[ServiceAdminController::class,'audits']);
});
