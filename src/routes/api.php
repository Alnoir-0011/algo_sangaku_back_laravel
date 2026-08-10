<?php

use App\Http\Controllers\V1\AuthenticationController;
use App\Http\Controllers\V1\ShrinesController;
use App\Http\Controllers\V1\User\SangakusController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/authentication', [AuthenticationController::class, 'store']);
    Route::resource('shrines', ShrinesController::class)->only(['index', 'show']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::delete('/authentication', [AuthenticationController::class, 'destroy']);
        Route::group(['prefix' => 'user'], function () {
            Route::resource('sangakus', SangakusController::class)
                ->only(['index', 'store', 'show', 'update', 'destroy'])
                ->where(['sangaku' => '[0-9]+']);
        });
    });
});
