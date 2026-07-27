<?php

use App\Http\Controllers\v1\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/authentication', [AuthenticationController::class, 'store']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::delete('/authentication', [AuthenticationController::class, 'destroy']);
    });
});
