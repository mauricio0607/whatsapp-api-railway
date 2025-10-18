<?php

use Illuminate\Support\Facades\Route;

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



Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rotas de teste
Route::get('/test/whatsapp-api', [App\Http\Controllers\TestController::class, 'testWhatsAppAPI']);
Route::post('/test/create-session', [App\Http\Controllers\TestController::class, 'createTestSession']);

// Rotas de teste para API Vercel
Route::prefix('test/vercel')->group(function () {
    Route::get('/connection', [App\Http\Controllers\WhatsAppTestController::class, 'testConnection']);
    Route::get('/sessions', [App\Http\Controllers\WhatsAppTestController::class, 'listSessions']);
    Route::post('/create-session', [App\Http\Controllers\WhatsAppTestController::class, 'createTestSession']);
    Route::get('/qr-code', [App\Http\Controllers\WhatsAppTestController::class, 'testQrCode']);
    Route::get('/page', [App\Http\Controllers\WhatsAppTestController::class, 'testPage']);
});

Route::group(['prefix' => 'cron', 'as' => 'cron.'], function (){

    Route::get('/execute-schedule', [App\Http\Controllers\CronController::class, 'ExecuteSchedule']);

    Route::get('/execute-webhook', [App\Http\Controllers\CronController::class, 'ExecuteWebhook']);

    Route::get('/notify-to-user', [App\Http\Controllers\CronController::class, 'notifyToUser']);
    Route::get('/remove-junk-device', [App\Http\Controllers\CronController::class, 'removeJunkDevice']);

});

Route::get('/local/{lang}',function($lang){
     \Session::put('locale', $lang);
     \App::setlocale($lang);
     
     return back();
});

//**======================== Payment Gateway Route Group for merchant ====================**//
Route::group(['middleware' => ['auth', 'web']], function () {
    Route::get('/payment/paypal', '\App\Gateway\Paypal@status');
    Route::post('/stripe/payment', '\App\Gateway\Stripe@status')->name('stripe.payment');
    Route::get('/stripe', '\App\Gateway\Stripe@view')->name('stripe.view');
    Route::get('/stripe-pay/success', '\App\Gateway\Stripe@status')->name('stripe.success');
    Route::get('/stripe-pay/fail', '\App\Gateway\Stripe@fail')->name('stripe.fail');


    Route::get('/payment/mollie', '\App\Gateway\Mollie@status');
    Route::post('/payment/paystack', '\App\Gateway\Paystack@status')->name('paystack.status');
    Route::get('/paystack', '\App\Gateway\Paystack@view')->name('paystack.view');
    Route::get('/payment/mercado', '\App\Gateway\Mercado@status')->name('mercadopago.status');
    Route::get('/razorpay/payment', '\App\Gateway\Razorpay@view')->name('razorpay.view');
    Route::post('/razorpay/status', '\App\Gateway\Razorpay@status');
    Route::get('/payment/flutterwave', '\App\Gateway\Flutterwave@status');
    Route::get('/payment/thawani', '\App\Gateway\Thawani@status');
    Route::get('/payment/instamojo', '\App\Gateway\Instamojo@status');
    Route::get('/payment/toyyibpay', '\App\Gateway\Toyyibpay@status');
    Route::get('/manual/payment', '\App\Gateway\CustomGateway@status');
    Route::get('payu/payment', '\App\Gateway\Payu@view')->name('payu.view');
    Route::post('payu/status', '\App\Gateway\Payu@status')->name('payu.status');
});
