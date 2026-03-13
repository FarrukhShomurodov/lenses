<?php

use App\Http\Controllers\Api\OneCController;
use App\Http\Controllers\Dashboard\Api\ImageController;
use App\Http\Controllers\Telegram\Api\CartController;
use App\Http\Controllers\Telegram\Api\FavoriteController;
use App\Http\Controllers\Telegram\Api\OrderController;
use App\Http\Controllers\Telegram\Api\PaycomController;
use App\Http\Controllers\Telegram\Api\ReviewController;
use App\Http\Controllers\Telegram\Api\UserController;
use Illuminate\Support\Facades\Route;

// delete image
Route::delete('delete/image/{folderName}/{fileName}', [ImageController::class, 'deletePhoto']);

Route::prefix('webapp')
    ->group(function () {

        // user
        Route::get('check-user', [UserController::class, 'checkActive']);
        Route::get('user/info', [UserController::class, 'info']);

        // favorites
        Route::get('favorite/list', [FavoriteController::class, 'list']);
        Route::post('favorite/toggle', [FavoriteController::class, 'toggle']);
        Route::get('favorite/check', [FavoriteController::class, 'check']);

        // cart
        Route::get('cart/data', [CartController::class, 'data']);
        Route::post('cart/add', [CartController::class, 'add']);
        Route::post('cart/update', [CartController::class, 'update']);
        Route::post('cart/remove', [CartController::class, 'remove']);
        Route::get('cart/count', [CartController::class, 'count']);
        Route::get('cart/items', [CartController::class, 'getProductQty']);

        // order
        Route::post('order/create', [OrderController::class, 'create']);
        Route::post('order/create-rx', [OrderController::class, 'createRx']);

        // reviews
        Route::get('reviews/list', [ReviewController::class, 'list']);
        Route::post('reviews/add', [ReviewController::class, 'add']);
    });

// paycom
Route::post('paycom', [PaycomController::class, 'handleRequest']);

// 1C integration
Route::prefix('1c')->group(function () {
    Route::get('product/{article}', [OneCController::class, 'product']);
    Route::post('sale', [OneCController::class, 'sale']);
});
