<?php

use App\Http\Controllers\Billing\DownloadInvoiceController;
use App\Http\Controllers\Orders\ShowPublicOrderController;
use App\Http\Controllers\Payments\PayPalWebhookController;
use App\Http\Controllers\Payments\StartPayPalCheckoutController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/offers', 'catalog.offers')->name('catalog.offers');

Route::get('/orders/{publicId}/{token}', ShowPublicOrderController::class)
    ->middleware('signed')
    ->name('orders.show');

Route::post('/orders/{publicId}/{token}/abandon', [ShowPublicOrderController::class, 'abandon'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('orders.abandon');

Route::post('/orders/{publicId}/{token}/payments/paypal', StartPayPalCheckoutController::class)
    ->middleware(['signed', 'throttle:5,1'])
    ->name('payments.paypal.start');

Route::view('/payments/paypal/return', 'payments.paypal-return')->name('payments.paypal.return');
Route::view('/payments/paypal/cancel', 'payments.paypal-cancel')->name('payments.paypal.cancel');

Route::post('/webhooks/paypal', PayPalWebhookController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.paypal');

Route::get('/invoices/{invoice}/pdf', DownloadInvoiceController::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('invoices.download');
