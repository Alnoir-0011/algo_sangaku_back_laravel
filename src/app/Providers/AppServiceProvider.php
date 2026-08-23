<?php

namespace App\Providers;

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
        Route::pattern('sangaku', '[0-9]+');
        Route::pattern('shrine', '[0-9]+');
    }
}
