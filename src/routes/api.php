<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\AuthenticationController;


Route::group(['prefix' => 'v1'], function () {
  Route::post('/authentication', [AuthenticationController::class, 'store']);

  Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::delete('/authentication', [AuthenticationController::class, 'destroy']);
  });
});
