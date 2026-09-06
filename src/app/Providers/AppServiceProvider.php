<?php

namespace App\Providers;

use App\Models\Answer;
use App\Models\AnswerResult;
use App\Observers\AnswerObserver;
use App\Observers\AnswerResultObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ID を取るルートパラメータは数値のみ受け付ける。
        // 数値以外は 404 とし、コントローラに到達させない。
        // 桁数を 18 までに制限しているのは、bigint に収まらない値が SQL に渡ると
        // Postgres が範囲外エラー（22003）を返して 500 になるため。
        Route::pattern('sangaku', '[0-9]{1,18}');
        Route::pattern('shrine', '[0-9]{1,18}');
        Route::pattern('answer', '[0-9]{1,18}');
        Route::pattern('answerResult', '[0-9]{1,18}');
        Route::pattern('user', '[0-9]{1,18}');

        Answer::observe(AnswerObserver::class);
        AnswerResult::observe(AnswerResultObserver::class);
    }
}
