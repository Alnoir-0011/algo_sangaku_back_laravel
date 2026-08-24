<?php

use App\Http\Controllers\V1\AuthenticationController;
use App\Http\Controllers\V1\SangakuSaveController;
use App\Http\Controllers\V1\SangakusController;
use App\Http\Controllers\V1\ShrineSangakusController;
use App\Http\Controllers\V1\ShrinesController;
use App\Http\Controllers\V1\User\DedicateController;
use App\Http\Controllers\V1\User\SangakusController as UserSangakusController;
use Illuminate\Support\Facades\Route;

// ルートパラメータ {sangaku} / {shrine} の数値制約は
// AppServiceProvider::boot() の Route::pattern() でまとめて定義している。
Route::prefix('v1')->group(function () {
    Route::post('/authentication', [AuthenticationController::class, 'store']);
    Route::apiResource('shrines', ShrinesController::class)->only(['index', 'show']);
    Route::get('/sangakus/{sangaku}', [SangakusController::class, 'show']);
    Route::get('/shrines/{shrine}/sangakus', [ShrineSangakusController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/authentication', [AuthenticationController::class, 'destroy']);

        Route::post('/sangakus/{sangaku}/save', SangakuSaveController::class);

        Route::prefix('user')->group(function () {
            Route::apiResource('sangakus', UserSangakusController::class);
            Route::post('/sangakus/{sangaku}/dedicate', DedicateController::class);
        });
    });
});
