<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiExceptionRenderer;
use Illuminate\Http\JsonResponse;

/**
 * コントローラの任意の位置からエラーレスポンスを返すためのショートハンド。
 *
 * 例外を投げて bootstrap/app.php でマッピングする方法とは異なり、
 * 業務ロジックの分岐でそのまま return できる。レスポンスの形状は
 * ApiExceptionRenderer に集約されているため、どちらの経路でも同じになる。
 */
trait ApiResponse
{
    /**
     * 任意のステータスコードでエラーレスポンスを返す。
     *
     * @param  array<string, mixed>  $extras
     */
    protected function renderError(int $status, string $message, ?string $errors = null, array $extras = []): JsonResponse
    {
        return ApiExceptionRenderer::render($status, $message, $errors, $extras);
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    protected function render400(?string $errors = null, array $extras = []): JsonResponse
    {
        return $this->renderError(400, 'Bad Request', $errors, $extras);
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    protected function render409(?string $errors = null, array $extras = []): JsonResponse
    {
        return $this->renderError(409, 'Conflict', $errors, $extras);
    }
}
