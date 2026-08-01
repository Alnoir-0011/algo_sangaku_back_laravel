<?php

use App\Exceptions\ApiExceptionRenderer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->report(function (Throwable $e) {
            Log::error($e->getMessage(), [
                'exception' => $e,
            ]);
        });

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => ApiExceptionRenderer::render(404, 'Record Not Found'));

        $exceptions->render(fn (ThrottleRequestsException $e, Request $request) => ApiExceptionRenderer::render(429, 'Too Many Requests', null, [
            'reset_at' => $e->getHeaders()['Retry-After'] ?? null,
        ]));

        $exceptions->render(fn (ValidationException $e, Request $request) => ApiExceptionRenderer::render(400, 'Bad Request', null, [
            'errors' => $e->errors(),
        ]));

        $exceptions->render(function (QueryException $e, Request $request) {
            if ((string) $e->getCode() === '23505') {
                return ApiExceptionRenderer::render(409, 'Conflict');
            } else {
                return null;
            }
        });

        $exceptions->render(fn (Throwable $e, Request $request) => ApiExceptionRenderer::render(500, 'Internal Server Error',
            app()->environment('production') ? null : $e->getMessage(),
        ));
    })->create();
