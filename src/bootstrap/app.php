<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Exceptions\GooglePlacesApiException;
use App\Exceptions\InvalidGoogleTokenException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->dontReport([
            NotFoundHttpException::class,
            ThrottleRequestsException::class,
            ValidationException::class,
            AuthenticationException::class,
            AuthorizationException::class,
            InvalidGoogleTokenException::class,
        ]);

        $exceptions->report(function (Throwable $e) {
            Log::error($e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        });

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => ApiExceptionRenderer::render(404, 'Record Not Found'));

        $exceptions->render(fn (ThrottleRequestsException $e, Request $request) => ApiExceptionRenderer::render(429, 'Too Many Requests', null, [
            'retry_after_seconds' => $e->getHeaders()['Retry-After'] ?? null,
        ]));

        $exceptions->render(fn (ValidationException $e, Request $request) => ApiExceptionRenderer::render(400, 'Bad Request', null, [
            'errors' => $e->errors(),
        ]));

        $exceptions->render(function (QueryException $e, Request $request) {
            if (in_array((string) $e->getCode(), ['23505', '23000'], true)) {
                return ApiExceptionRenderer::render(409, 'Conflict');
            } else {
                return null;
            }
        });

        $exceptions->render(fn (AuthenticationException $e, Request $request) => ApiExceptionRenderer::render(401, 'Unauthenticated'));

        $exceptions->render(fn (InvalidGoogleTokenException $e, Request $request) => ApiExceptionRenderer::render(401, 'Unauthenticated'));

        $exceptions->render(fn (AuthorizationException $e, Request $request) => ApiExceptionRenderer::render(403, 'Forbidden'));

        $exceptions->render(fn (GooglePlacesApiException $e, Request $request) => ApiExceptionRenderer::render(502, 'Bad Gateway'));

        $exceptions->render(fn (HttpExceptionInterface $e, Request $request) => ApiExceptionRenderer::render($e->getStatusCode(), 'Error'));

        $exceptions->render(fn (Throwable $e, Request $request) => ApiExceptionRenderer::render(500, 'Internal Server Error',
            config('app.debug') ? $e->getMessage() : null,
        ));
    })->create();
