<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductScrapeController;

Route::prefix('v1')->group(function () {
    Route::post('/scrape', [ProductScrapeController::class, 'scrape']);
    Route::get('/products', [ProductScrapeController::class, 'index']);
    Route::get('/products/{id}', [ProductScrapeController::class, 'show']);
});