<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

final class ApiExceptionRenderer
{
    public static function render(int $status, string $message, ?string $details = null, array $extras = []): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $message,
            'errors' => array_filter([$details]),
            ...$extras,
        ]), $status);
    }
}
