<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/offers', 'catalog.offers')->name('catalog.offers');
