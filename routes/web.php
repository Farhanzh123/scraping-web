<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopeeController;

Route::get('/shopee', [ShopeeController::class, 'index'])->name('shopee.index');
Route::post('/shopee/login', [ShopeeController::class, 'launchChrome'])->name('shopee.login');
Route::post('/shopee/scrape', [ShopeeController::class, 'triggerScrape'])->name('shopee.scrape');

// Route Check & Save Sesi
Route::post('/shopee/check-session', [ShopeeController::class, 'checkSession'])->name('shopee.check-session');
Route::post('/shopee/save-session', [ShopeeController::class, 'saveSession'])->name('shopee.save-session');
Route::post('/shopee/update-settings', [ShopeeController::class, 'updateSettings'])->name('shopee.update-settings');