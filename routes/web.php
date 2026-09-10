<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UpdateEmailVerifiedController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//URL::forceScheme('https');


// Route::get('/', function () {
//     return view('home.index', ['pageTitle' => "Login"]);
// });

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/about', [App\Http\Controllers\HomeController::class, 'about'])->name('about');
Route::get('/contact', [App\Http\Controllers\HomeController::class, 'contact'])->name('contact');
Route::get('/faq', [App\Http\Controllers\HomeController::class, 'faq'])->name('faq');

Route::get('/terms', [App\Http\Controllers\HomeController::class, 'terms'])->name('terms');
Route::get('/privacy', [App\Http\Controllers\HomeController::class, 'privacy'])->name('privacy');

Route::get('/pricing', [App\Http\Controllers\HomeController::class, 'pricing'])->name('pricing');


Route::get('/login', function () {
    return view('layouts.main');
});

Route::get('/register', function () {
    return view('layouts.main');
});

Route::get('/verify-email', function () {
    return view('layouts.main');
});

Route::get('/forgotpassword', function () {
    return view('layouts.main');
});

Route::get('/verifytoken', function () {
    return view('layouts.main');
});

Route::get('/reset', function () {
    return view('layouts.main');
});


Route::get('/dashboard', function () {
    return view('layouts.master');
});

Route::get('/dashboard/{any}', function () {
    return view('layouts.master');
})->where('any', '.*');


Route::get('/email-verify/{user}', UpdateEmailVerifiedController::class)
    ->middleware('signed')
    ->name('email.verify');

Route::get('/my-cron', [App\Http\Controllers\HomeController::class,'run_cron'])->name('mycron');

//Route::get('/my-reverse-script', [App\Http\Controllers\API\OrderController::class,'updateOrderExternal'])->middleware('admin.only')->name('myreverse');

Route::get('/airtime-nigeria-requery/{ref}',[App\Http\Controllers\HomeController::class,'airtime_nigeria_requery'])->name('angrequery');

Route::webhooks('monify-webhook', 'monify-webhook');
Route::webhooks('rehoboth-webhook', 'rehoboth-webhook');
Route::webhooks('rave-webhook', 'rave-webhook');
Route::webhooks('providus-webhook', 'providus-webhook');
Route::webhooks('gtsquadco-webhook', 'gtsquadco-webhook');
Route::webhooks('ogadam-webhook', 'ogadam-webhook');
Route::webhooks('tbch-webhook', 'tbch-webhook');
Route::webhooks('autosync-webhook', 'autosync-webhook');
Route::webhooks('smeplug-webhook', 'smeplug-webhook');
Route::webhooks('wisper-webhook', 'wisper-webhook');
Route::webhooks('palmpay-webhook', 'palmpay-webhook');
Route::webhooks('opay-wallet-webhook', 'opay-wallet-webhook')
    ->middleware('throttle:120,1');
Route::webhooks('budpay-webhook', 'budpay-webhook');

Route::get('/export-transactions',[App\Http\Controllers\API\OrderController::class,
            'exportTransactions'])->name('exportTransactions');

//MTN SME (WEBSITE)
Route::get('/mtn-sme-cloud-login',[App\Http\Controllers\MtnSmeController::class,
'automationLoginView'])->name('automationLoginView');

Route::post('/mtn-sme-cloud-validate-profile',[App\Http\Controllers\MtnSmeController::class,
'validateMtnProfile'])->name('validateMtnProfile');

Route::post('/mtn-sme-cloud-generate-otp',[App\Http\Controllers\MtnSmeController::class,
'generateMtnOtp'])->name('generateMtnOtp');

Route::post('/mtn-sme-cloud-validate-otp',[App\Http\Controllers\MtnSmeController::class,
'validateMtnOtp'])->name('validateMtnOtp');

Route::get('/mtn-sme-cloud-account-active',[App\Http\Controllers\MtnSmeController::class,
'makeAccountActive'])->name('makeAccountActive');



//MTN SME (APP)
Route::get('/mtn-sme-app-login',[App\Http\Controllers\MtnSmeAppController::class,
'automationLoginView'])->name('automationLoginViewApp');


Route::post('/mtn-sme-app-generate-otp',[App\Http\Controllers\MtnSmeAppController::class,
'generateMtnOtp'])->name('generateMtnOtpApp');

Route::post('/mtn-sme-app-validate-otp',[App\Http\Controllers\MtnSmeAppController::class,
'validateMtnOtp'])->name('validateMtnOtpApp');

Route::get('/mtn-sme-app-account-active',[App\Http\Controllers\MtnSmeAppController::class,
'makeAccountActive'])->name('makeAccountActiveApp');

//MTN DIRECT (APP)
Route::get('/mtn-direct-app-login',[App\Http\Controllers\MtnDirectAppController::class,
'automationLoginView'])->name('automationLoginViewApp');

Route::post('/mtn-direct-app-generate-otp',[App\Http\Controllers\MtnDirectAppController::class,
'generateMtnOtp'])->name('generateMtnOtpApp');

Route::post('/mtn-direct-app-validate-otp',[App\Http\Controllers\MtnDirectAppController::class,
'validateMtnOtp'])->name('validateMtnOtpApp');

Route::get('/mtn-direct-app-account-active',[App\Http\Controllers\MtnDirectAppController::class,
'makeAccountActive'])->name('makeAccountActiveApp');


// MTN CG
Route::get('/mtn-cg-cloud-login',[App\Http\Controllers\MtnCgController::class,
'automationLoginView'])->name('automationLoginViewCG');

Route::post('/mtn-cg-validate-profile',[App\Http\Controllers\MtnCgController::class,
'validateMtnProfile'])->name('validateMtnProfileCG');

Route::post('/mtn-cg-generate-otp',[App\Http\Controllers\MtnCgController::class,
'generateMtnOtp'])->name('generateMtnOtpCG');

Route::post('/mtn-cg-validate-otp',[App\Http\Controllers\MtnCgController::class,
'validateMtnOtp'])->name('validateMtnOtpCG');

// Route::get('/mtn-cg-account-active',[App\Http\Controllers\MtnCgController::class,
// 'makeAccountActive'])->name('makeAccountActiveCG');

Route::get('/mtn-cg-account-active',[App\Http\Controllers\MtnCgController::class,
'keepTokenAlive'])->name('makeAccountActiveCG');



// Route::get('/send-notification', [NotificationController::class, 'sendtransationNotification']);


// Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'isAdmin']], function () {
//     //Route::get('dashboard', 'AdminController@index');
//     Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'index'])->name('admin-dashboard');
//     Route::get('profile', [App\Http\Controllers\AdminController::class, 'profile'])->name('admin-profile');

//     Route::get('data', [App\Http\Controllers\AdminController::class, 'products'])->name('products');
//     Route::get('orders', [App\Http\Controllers\AdminController::class, 'orders'])->name('orders');
//     Route::get('drivers', [App\Http\Controllers\AdminController::class, 'drivers'])->name('drivers');
//     Route::get('payments', [App\Http\Controllers\AdminController::class, 'payments'])->name('payments');
//     Route::get('categories', [App\Http\Controllers\AdminController::class, 'categories'])->name('categories');
//     Route::get('settings', [App\Http\Controllers\AdminController::class, 'settings'])->name('settings');

//     Route::get('farmers', [App\Http\Controllers\AdminController::class, 'farmers'])->name('farmers');
//     Route::get('delivery-request', [App\Http\Controllers\AdminController::class, 'delivery_requests'])->name('delivery-request');
//     Route::get('track-order/{id}', [App\Http\Controllers\AdminController::class, 'track_order'])->name('track-order');

//     Route::get('farmer-details/{id}', [App\Http\Controllers\AdminController::class, 'farmer_details'])->name('admin-farmer-details');
//     Route::get('user-details/{id}', [App\Http\Controllers\AdminController::class, 'user_details'])->name('user-details');
//     Route::get('delivery-log/{id}', [App\Http\Controllers\AdminController::class, 'delivery_log'])->name('admin-delivery-log');
//     Route::get('users/{role}', [App\Http\Controllers\AdminController::class, 'users'])->name('users');

//     // Route::get('track-order/{id}', [App\Http\Controllers\AdminController::class, 'track_order'])->name('track-order');


//     Route::get('commissions', [App\Http\Controllers\AdminController::class, 'commissions'])->name('commissions');
//     Route::get('withdrawals', [App\Http\Controllers\AdminController::class, 'withdrawals'])->name('withdrawals');
//     Route::get('map', [App\Http\Controllers\AdminController::class, 'map'])->name('map');
//     Route::get('settings', [App\Http\Controllers\AdminController::class, 'settings'])->name('settings');
// });

// Route::group(['prefix' => 'dashboard', 'middleware' => ['auth', 'isUser']], function () {
//     Route::get('home', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
//     Route::get('commissions', [App\Http\Controllers\DashboardController::class, 'commissions'])->name('agent-commissions');
//     Route::get('withdrawals', [App\Http\Controllers\DashboardController::class, 'withdrawals'])->name('agent-withdrawals');
//     Route::get('profile', [App\Http\Controllers\DashboardController::class, 'profile'])->name('profile');
//     Route::get('data', [App\Http\Controllers\DashboardController::class, 'data'])->name('data');

//     Route::get('airtime', [App\Http\Controllers\DashboardController::class, 'airtime'])->name('airtime');
// });

// Route::get('dashboard/fundaccount', [FundController::class, 'index'])->name('index');
// Route::post('flutterwave/payment', [FundController::class, 'store'])->name('flutterwave.payment');
// Route::get('flutterwave/callback', [FundController::class, 'callback'])->name('flutterwave-callback');



// Route::middleware(['auth', 'isAdmin'])->group(function () {
//     Route::get('/admin', function () {
//         return view('admin.dashboard');
//     })->name('dashboard');
// });


// Route::middleware(['auth', 'isAdmin'])->group(function () {
//     Route::get('/admin', function () {
//         return view('admin.dashboard');
//     })->name('dashboard');
// });

// Route::get('/login', function () {
//     return view('auth.login');
// });

// Route::get('{any}', function () {
//     return view('layouts.master');
// })->where('any', '.*');


//Auth::routes(['verify' => true]);
//URL::forceScheme('https');

// Auth::routes();


// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
// Route::get('/users', [App\Http\Controllers\HomeController::class, 'users'])->name('users');
