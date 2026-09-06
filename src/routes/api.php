<?php

use App\Http\Controllers\V1\AuthenticationController;
use App\Http\Controllers\V1\ProfilesController;
use App\Http\Controllers\V1\SangakuSaveController;
use App\Http\Controllers\V1\SangakusController;
use App\Http\Controllers\V1\ShrineSangakusController;
use App\Http\Controllers\V1\ShrinesController;
use App\Http\Controllers\V1\User\AnswerResultsController;
use App\Http\Controllers\V1\User\AnswersController;
use App\Http\Controllers\V1\User\DedicateController;
use App\Http\Controllers\V1\User\ProfilesController as UserProfilesController;
use App\Http\Controllers\V1\User\ResultsController;
use App\Http\Controllers\V1\User\SangakusController as UserSangakusController;
use App\Http\Controllers\V1\User\SavedSangakuAnswersController;
use App\Http\Controllers\V1\User\SavedSangakuIdsController;
use App\Http\Controllers\V1\User\SavedSangakusController;
use Illuminate\Support\Facades\Route;

// ルートパラメータ {sangaku} / {shrine} の数値制約は
// AppServiceProvider::boot() の Route::pattern() でまとめて定義している。
Route::prefix('v1')->group(function () {
    Route::post('/authentication', [AuthenticationController::class, 'store']);
    Route::apiResource('shrines', ShrinesController::class)->only(['index', 'show']);
    Route::get('/sangakus/{sangaku}', [SangakusController::class, 'show']);
    Route::get('/shrines/{shrine}/sangakus', [ShrineSangakusController::class, 'index']);
    Route::get('/profiles/{user}', [ProfilesController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/authentication', [AuthenticationController::class, 'destroy']);

        Route::post('/sangakus/{sangaku}/save', SangakuSaveController::class);

        Route::prefix('user')->group(function () {
            Route::apiResource('sangakus', UserSangakusController::class);
            Route::post('/sangakus/{sangaku}/dedicate', DedicateController::class);
            Route::get('/sangakus/{sangaku}/result', [ResultsController::class, 'show']);
            Route::resource('saved-sangakus', SavedSangakusController::class)->only(['index', 'show']);
            Route::post('/saved_sangakus/{sangaku}/answers', [SavedSangakuAnswersController::class, 'store']);
            Route::get('/saved_sangakus/{sangaku}/answers', [SavedSangakuAnswersController::class, 'show']);
            Route::get('/answers/{answer}', [AnswersController::class, 'show']);
            Route::get('/answer_results/{answerResult}', [AnswerResultsController::class, 'show']);
            Route::get('/saved_sangaku_ids', [SavedSangakuIdsController::class, 'index']);
            Route::get('/profile', [UserProfilesController::class, 'show']);
            Route::patch('/profile', [UserProfilesController::class, 'update']);
        });
    });
});
