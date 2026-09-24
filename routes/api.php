<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CommissionController;
use App\Http\Controllers\API\SubcategoryController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\BucketOrderController;
use App\Http\Controllers\API\BucketController;

use App\Http\Controllers\API\WithdrawalController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\SlidesController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

use App\Http\Controllers\API\PinController;
use App\Http\Controllers\API\ReferralController;
use App\Http\Controllers\API\ReferralAdminController;
use App\Http\Controllers\API\WhatsAppTransactionAdminController;
use App\Http\Controllers\API\OpayWalletController;
use App\Http\Controllers\API\TransactionPinController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//URL::forceScheme('https');


Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register')->middleware('throttle:5,60');
    Route::post('email/verification/resend', 'resendVerification')->middleware('throttle:3,60');
    Route::post('login', [ 'as' => 'login', 'uses' => 'login'])->middleware('throttle:login');

    // Route::post('login', 'login'); 
    

    //Route::post('password/reset', 'Auth\ResetPasswordController@reset');
    Route::webhooks('monnify_webhook');
});

Route::post('auth/password/email', [ForgotPasswordController::class, 'sendEmail']);
Route::post('auth/password/email_mobile', [ForgotPasswordController::class, 'sendEmail']);
Route::post('auth/token/verify', [ForgotPasswordController::class, 'verifyToken']);
Route::post('auth/password/reset', [ResetPasswordController::class, 'reset']);
Route::get('app/dashboard-update', [SettingController::class, 'dashboardUpdate']);

Route::get('my_cron', [App\Http\Controllers\API\OrderController::class, 'run_cron'])->name('cron_job');
Route::middleware('auth:api')->group(function () {
    Route::get('transaction-pin/status', [TransactionPinController::class, 'status']);
    Route::post('transaction-pin', [TransactionPinController::class, 'store'])->middleware('throttle:5,1');
    Route::put('transaction-pin', [TransactionPinController::class, 'update'])->middleware('throttle:5,1');
    Route::post('transaction-pin/reset', [TransactionPinController::class, 'reset'])->middleware('throttle:3,1');
    Route::post('/logout', [RegisterController::class, 'logout']);
});

Route::middleware('auth:api')->prefix('admin')->group(
    function () {
        Route::post('user/update/wallet', [App\Http\Controllers\API\UserController::class, 'updateWallet'])->name('updateWallet')->middleware('admin.only');
        Route::post('user/update/commission', [App\Http\Controllers\API\UserController::class, 'updateCommissionWallet'])->name('updateCommissionWallet')->middleware('admin.only');
        Route::post('user/update/levels', [App\Http\Controllers\API\UserController::class, 'updateLevel'])->name('updateLevels')->middleware('admin.only');
        Route::resource('allusers', UserController::class)->middleware('admin.only');
        Route::get('allusers/bucket-switch-toggle/{user_id}', [UserController::class, 'bucketSwitcher'])->middleware('admin.only');
        Route::resource('settings', SettingController::class)->middleware('admin.only');
        Route::put('app-dashboard-update', [SettingController::class, 'updateDashboardUpdate'])->middleware('admin.only');
        Route::resource('subcategories', SubcategoryController::class)->middleware('admin.only');
        Route::get('referrals', [ReferralAdminController::class, 'index'])->middleware('admin.only');
        Route::get('referrals/earnings-summary', [ReferralAdminController::class, 'earningsSummary'])->middleware('admin.only');
        Route::put('referrals/settings', [ReferralAdminController::class, 'updateSettings'])->middleware('admin.only');
        Route::get('whatsapp-transactions', [WhatsAppTransactionAdminController::class, 'index'])->middleware('admin.only');
        Route::put('whatsapp-transactions/{id}', [WhatsAppTransactionAdminController::class, 'update'])->middleware('admin.only');
        Route::post('whatsapp-transactions/{id}/refund', [WhatsAppTransactionAdminController::class, 'refund'])->middleware('admin.only');

        Route::resource('slides', SlidesController::class);
        Route::post('slides/uploadimage', [App\Http\Controllers\API\SlidesController::class, 'uploadImage'])->name('slides/uploadimage')->middleware('admin.only');
    }
);


Route::middleware('auth:api')->group(function () {
    Route::get('opay/wallet', [OpayWalletController::class, 'show']);
    Route::post('opay/wallet', [OpayWalletController::class, 'store'])->middleware('throttle:5,1');
    Route::get('opay/wallet/balance', [OpayWalletController::class, 'balance'])->middleware('throttle:20,1');
    Route::get('opay/wallet/transactions', [OpayWalletController::class, 'transactions'])->middleware('throttle:20,1');
    Route::get('referrals/dashboard', [ReferralController::class, 'show']);
    Route::get('referrals/downline', [ReferralController::class, 'referrals']);
    // Route::resource('products', ProductController::class);
    Route::resource('orders', OrderController::class);
    Route::get('export-transactions', [OrderController::class, 'exportTransactions'])
        ->name('api.exportTransactions');
    Route::post('orders/update-status', [OrderController::class,'updateStatus'])->name('update_status')->middleware('admin.only');
    Route::post('order/reports', [App\Http\Controllers\API\OrderController::class, 'getReport'])->middleware('admin.only');
     Route::post('accounting/reports', [App\Http\Controllers\API\OrderController::class, 'getAccountingReport'])->middleware('admin.only');
    
    Route::resource('bucket-orders', BucketOrderController::class);
    Route::put('update/bucket-order/{id}', [App\Http\Controllers\API\BucketOrderController::class, 'updateOrder'])->middleware('admin.only')->name('update_order');
    Route::post('bucket-order/reports', [App\Http\Controllers\API\BucketOrderController::class, 'getReport'])->middleware('admin.only');
    
    Route::post('bucket-orders/update-status', [BucketOrderController::class,'updateStatus'])->name('update_bucket_status')->middleware('admin.only');
    
    Route::get('bucket-orders/bucket/{id}', [App\Http\Controllers\API\BucketOrderController::class, 'getBucketTransaction'])->middleware('admin.only')->name('bucket_transaction');
    
    Route::get('switch-off-bucket-purchase/{id}', [App\Http\Controllers\API\BucketController::class, 'switchOffBucketPurchase'])->middleware('admin.only')->name('switchOffBucketPurchase');

    Route::resource('subcategory', SubcategoryController::class)->except(['destroy', 'store']);
    
    Route::get('accounting', [SubcategoryController::class, 'load_accounting'])->name('accounting_setup')->middleware('admin.only');

    Route::get('total-wallet', [UserController::class, 'getTotalWallet'])->name('user_wallet')->middleware('admin.only');
    
    Route::resource('pins', PinController::class)->except(['destroy', 'store']);
    Route::post('add-pins', [PinController::class,'create'])->middleware('admin.only')->name('add_pin');
    Route::put('update-pin/{id}', [PinController::class,'update'])->middleware('admin.only')->name('update_pin');
    Route::get('delete-pins/{id}/{title}', [PinController::class,'destroy'])->middleware('admin.only')->name('delete_pin');
    Route::get('get/plans', [App\Http\Controllers\API\SubcategoryController::class, 'load_public_plans'])->name('subcategory/load_public_plans');
    Route::get('get/subcategory/plans/{category_id}', [App\Http\Controllers\API\SubcategoryController::class, 'load_plans'])->name('subcategory/load_plans');
    
    
    Route::resource('bucket', BucketController::class)->except(['destroy', 'store']);
    Route::get('get-all-buckets', [App\Http\Controllers\API\BucketController::class, 'load_admin_bucket'])->middleware('admin.only')->name('load_admin_bucket');
    Route::get('get_all_bucket_balances', [App\Http\Controllers\API\BucketController::class, 'get_all_bucket_balances'])->name('get_all_bucket_balances');


    Route::get('buckets', [App\Http\Controllers\API\BucketController::class, 'load_bucket'])->name('load_bucket');
    Route::get('buckets/balance', [App\Http\Controllers\API\BucketController::class, 'load_bucket_balance'])->name('load_bucket_balance');

    Route::get('bucket/{id}', [App\Http\Controllers\API\BucketController::class, 'show'])->name('show');

    Route::put('update-bucket/{id}', [BucketController::class,'update'])->middleware('admin.only')->name('update_bucket');
    Route::get('delete-bucket/{id}', [BucketController::class,'destroy'])->middleware('admin.only')->name('delete_bucket');
    
    
    Route::post('purchase/bucket', [App\Http\Controllers\API\BucketOrderController::class, 'PurchaseBucket'])->name('purchase_bucket');

    Route::post('purchase/bucket/data', [App\Http\Controllers\API\BucketOrderController::class, 'PurchaseData'])->name('purchase_data');

    

    Route::resource('categories', CategoryController::class);
    Route::resource('commissions', CommissionController::class);
    Route::resource('withdrawals', WithdrawalController::class);
    Route::resource('users', UserController::class)->except(['destroy', 'store']);

    Route::post('change-password', [App\Http\Controllers\API\UserController::class, 'change_password'])->name('change-password');
    Route::post('users/uploadimage/{id}', [App\Http\Controllers\API\UserController::class, 'uploadImage'])->name('users/uploadimage');
    Route::post('categories/uploadimage/{id}', [App\Http\Controllers\API\CategoryController::class, 'uploadImage'])->middleware('admin.only')->name('categories/uploadimage');
    Route::post('orders/postPayment', [App\Http\Controllers\API\OrderController::class, 'postPayment'])->middleware('admin.only')->name('orders/postPayment');

    Route::get('load-home', [App\Http\Controllers\API\UserController::class, 'loadHomePage'])->name('loadHomePage');
    
    // MTN LOGINS
    Route::get('load-logins', [App\Http\Controllers\API\MtnLoginController::class, 'load_mtn_logins'])->middleware('admin.only')->name('loadMtnLogins');

    Route::get('activate-number/{id}', [App\Http\Controllers\API\MtnLoginController::class, 'activate_number'])->middleware('admin.only')->name('activateNumber');

    Route::get('delete-number/{id}', [App\Http\Controllers\API\MtnLoginController::class,'destroy'])->middleware('admin.only')->name('delete_login');


    Route::post('purchase/airtime', [App\Http\Controllers\API\OrderController::class, 'purchaseAirtime'])->middleware('transaction.pin')->name('orders/purchaseAirtime');
    Route::post('purchase/data', [App\Http\Controllers\API\OrderController::class, 'purchaseData'])->middleware('transaction.pin')->name('orders/purchaseData');
    Route::post('purchase/talkmore', [App\Http\Controllers\API\OrderController::class, 'purchaseTalkmore'])->middleware('transaction.pin')->name('orders/purchaseTalkmore');
    Route::get('reprocess/data/{order_id}', [App\Http\Controllers\API\ReprocessController::class, 'reprocessData'])->middleware('admin.only')->name('orders/repurchaseData');
    Route::post('purchase/cable', [App\Http\Controllers\API\OrderController::class, 'purchaseCable'])->middleware('transaction.pin')->name('orders/purchaseCable');
    Route::post('purchase/electricity', [App\Http\Controllers\API\OrderController::class, 'purchaseElectricity'])->middleware('transaction.pin')->name('orders/purchaseElectricity');
    Route::post('purchase/exam', [App\Http\Controllers\API\OrderController::class, 'purchaseExam'])->middleware('transaction.pin')->name('orders/purchaseExam');
    Route::post('fund/withdraw', [App\Http\Controllers\API\OrderController::class, 'withdraw'])->middleware('transaction.pin')->name('orders/withdraw');

    Route::post('send/sms', [App\Http\Controllers\API\OrderController::class, 'sendSMS'])->middleware('transaction.pin')->name('orders/sendSMS');
    Route::post('initiate-a2cash', [App\Http\Controllers\API\OrderController::class, 'initiateA2cash'])->name('orders/initiateA2cash');
    Route::post('purchase/a2cash', [App\Http\Controllers\API\OrderController::class, 'purchaseA2cash'])->middleware('transaction.pin')->name('orders/purchaseA2cash');
    Route::get('fetch/bouquet', [App\Http\Controllers\API\OrderController::class, 'fetchBouquet'])->name('orders/fetchBouquet');
    Route::post('verify/card', [App\Http\Controllers\API\OrderController::class, 'verifyCard'])->name('orders/verifyCard');
    Route::post('verify/card/app', [App\Http\Controllers\API\OrderController::class, 'verifyCardApp'])->name('orders/verifyCardApp');
    Route::get('reserve/account/{bank}', [App\Http\Controllers\API\UserController::class, 'generate_reserved_account'])->name('users/generate_reserved_account');
    Route::post('payment/initiate', [App\Http\Controllers\API\PaymentController::class, 'initiatePayment'])->name('initiatePayment');
    Route::post('payment/initiateOrder', [App\Http\Controllers\API\PaymentController::class, 'initiateOrder'])->name('initiateOrder');

    Route::post('payment/manual/initiate', [App\Http\Controllers\API\PaymentController::class, 'initiateManualPayment'])->name('initiateManualPayment');

    Route::get('generate/api/key', [App\Http\Controllers\API\UserController::class, 'generateApiKey'])->name('users/generateApiKey');
    Route::post('update/profile/bvn', [App\Http\Controllers\API\UserController::class, 'updateBvn'])->name('users/updateBvn');
    Route::post('update/profile/nin', [App\Http\Controllers\API\UserController::class, 'updateNin'])->name('users/updateNin');
    
    Route::get('verify-payment/{transaction_reference}', [App\Http\Controllers\API\UserController::class, 'VerifyPayment'])->name('users/VerifyPayment');

    Route::post('generate-payment-link', [App\Http\Controllers\API\UserController::class, 'initializeAtmPayment'])->name('users/initializeAtmPayment');
    
    Route::post('generate-palmpay-payment-link', [App\Http\Controllers\API\UserController::class, 'initializePalmpayAtm'])->name('users/initializePalmpayAtm');
    
    Route::post('palmpay-bank-transfer', [App\Http\Controllers\API\UserController::class, 'initializePalmpayTransfer'])->name('users/initializePalmpayTransfer');


    Route::put('update/order/{id}', [App\Http\Controllers\API\OrderController::class, 'updateOrder'])->middleware('admin.only')->name('updateOrder');
    Route::put('update/order/confirmed/{id}', [App\Http\Controllers\API\OrderController::class, 'updateConfirmedOrder'])->middleware('admin.only')->name('updateConfirmedOrder');
    Route::put('update/bucket-order/confirmed/{id}', [App\Http\Controllers\API\BucketOrderController::class, 'updateConfirmedOrder'])->middleware('admin.only')->name('updateBucketConfirmedOrder');

    Route::get('payment/confirm/{ref}', [App\Http\Controllers\API\PaymentController::class, 'ConfirmPayment'])->name('ConfirmPayment');
    Route::get('payment/confirmorder/{ref}', [App\Http\Controllers\API\PaymentController::class, 'ConfirmOrder'])->middleware('admin.only')->name('ConfirmOrder');

    Route::post('user/update/level', [App\Http\Controllers\API\UserController::class, 'updateLevel'])->name('updateLevel');
    Route::post('user/admin-update/level', [App\Http\Controllers\API\UserController::class, 'updateLevelAdmin'])->middleware('admin.only')->name('updateLevelAdmin');
    Route::post('user/bonus/transfer', [App\Http\Controllers\API\UserController::class, 'transferBonus'])->middleware('transaction.pin')->name('users/transferBonus');

    Route::get('user/settings', [App\Http\Controllers\API\UserController::class, 'usersettings'])->name('users/usersettings');
    Route::get('user/generateProvidus', [App\Http\Controllers\API\UserController::class, 'generateProvidus'])->name('users/generateProvidus');

    Route::post('fetch/ringobouquet', [App\Http\Controllers\API\OrderController::class, 'ringoFetchBouquet'])->name('orders/ringoFetchBouquet');
    Route::post('verify/ringo', [App\Http\Controllers\API\OrderController::class, 'ringoVerify'])->name('orders/ringoVerify');
    Route::post('providus/repush', [App\Http\Controllers\API\OrderController::class, 'provRepush'])->name('orders/provRepush');
    Route::get('requery/{ref}',[App\Http\Controllers\API\OrderController::class, 'getTransaction'])->name('orders/getTransaction');
    Route::get('requery/a-order/{id}',[App\Http\Controllers\API\OrderController::class, 'requeryAdminTransaction'])->middleware('admin.only')->name('orders/requeryAdminTransaction');
    Route::get('requery/order/{id}',[App\Http\Controllers\API\OrderController::class, 'requeryUserTransaction'])->name('orders/requeryUserTransaction');
    Route::put('/update-webhook',[App\Http\Controllers\API\UserController::class,
            'updateWebhook'])->name('update-webhook');
    Route::get('get-mtn-tokens',[App\Http\Controllers\API\SettingController::class, 'getTokensMtn'])->name('settings/getTokensMtn');
    Route::get('toggle-all-packages/{title}',[App\Http\Controllers\API\SubcategoryController::class, 'toggleAllPackages'])->name('subcategory/toggleAllPackages');
    Route::get('toggle-active-package/{id}',[App\Http\Controllers\API\SubcategoryController::class, 'toggleActivePackage'])->name('subcategory/toggleActivePackage');

});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


//URL::forceScheme('https');
