<?php

use App\Http\Controllers\Orders\ShowPublicOrderController;
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
