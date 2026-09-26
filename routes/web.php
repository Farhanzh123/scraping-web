<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopeeController;

Route::controller(ShopeeController::class)->prefix('shopee')->name('shopee.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/login', 'launchChrome')->name('login');
    Route::post('/scrape', 'triggerScrape')->name('scrape');
    Route::post('/check-session', 'checkSession')->name('check-session');
    Route::post('/save-session', 'saveSession')->name('save-session');
    Route::post('/update-settings', 'updateSettings')->name('update-settings');
});